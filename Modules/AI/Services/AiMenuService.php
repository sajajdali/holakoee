<?php

namespace Modules\AI\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\app\Models\AiRequest;
use Modules\AI\Services\Traits\CashAiTrait;
use Modules\Diet\Entities\BasicFood;
use Modules\Setting\Enum\SettingKeyEnum;

class AiMenuService
{
    use CashAiTrait;
    /**
     * خروجی خام OpenAI (همان $response->json()) با: کش + لاک + چک توکن + ثبت مصرف.
     * - روی cache hit: به طور پیش‌فرض هزینه صفر (قابل تنظیم).
     */
    public function fetchRaw(array $foods, string $foodName, bool $refresh = false): ?array
    {
        // 0) چک موجودی حداقلی قبل از شروع (قابل تنظیم)
        $minPreTokens = (int) config('ai.billing.min_pre_tokens', 1);
        $this->ensureSufficientTokens($minPreTokens);

        // 1) DB & prompt
        $basicFoods   = $this->queryBasicFoods($foods);
        $foodsJson    = $basicFoods->toJson(JSON_UNESCAPED_UNICODE);
        $replaceFoods = implode('-', $foods);

        $basePrompt   = setting(SettingKeyEnum::AI_PROMPT);
        $prompt       = str_replace(['{foods}', '{foodName}'], [$replaceFoods, $foodName], $basePrompt);

        // 2) cache key
        $foodsMaxUpdatedAt = optional(BasicFood::max('updated_at'), fn($v) => Carbon::parse($v)->timestamp) ?? 0;
        $cacheKey = sprintf('ai:menu:v1:%s:%s', $foodsMaxUpdatedAt, sha1($prompt.$foodsJson));

        $cacheEnabled = (bool) config('ai.cache.enabled', true);
        $useTags      = (bool) config('ai.cache.use_tags', false);
        $tag          = (string) config('ai.cache.tag', 'ai-menu');

        if ($refresh && $cacheEnabled) {
            Cache::forget($cacheKey);
        }

        // 2.1) اگر در کش موجود است، تصمیم بگیریم با آن چه کنیم
        if ($cacheEnabled && Cache::has($cacheKey)) {
            $cached = $useTags && method_exists(Cache::getStore(), 'tags')
                ? Cache::tags([$tag])->get($cacheKey)
                : Cache::get($cacheKey);

            if (is_array($cached)) {
                $this->indexCacheKey($cacheKey, $cached, $foodName, $foods);

                $this->maybeChargeOnCacheHit($cached, $prompt);
                return $cached;
            }
            // اگر کش خراب بود، می‌افتیم سراغ محاسبه
        }

        // 3) محاسبه و ذخیره نتیجه (به همراه ثبت مصرف بعد از پاسخ موفق)
        $compute = function () use ($prompt, $foodsJson, $cacheKey) {
            $lock = Cache::lock('lock:'.$cacheKey, 30);
            try {
                return $lock->block(10, function () use ($prompt, $foodsJson) {
                    $raw = $this->callOpenAi($prompt, $foodsJson); // ⬅️ خام
                    if (!$raw || !is_array($raw)) {
                        return null;
                    }

                    // ثبت لاگ AiRequest + کسر توکن (فقط روی پاسخ موفق واقعی)
                    $this->recordUsageAndDebit($raw, $prompt);

                    return $raw;
                });
            } finally {
                optional($lock)->release();
            }
        };

        $result = $compute();

        if (!$cacheEnabled || !$result) {
            return $result;
        }


        if ($useTags && method_exists(Cache::getStore(), 'tags')) {
            Cache::tags([$tag])->forever($cacheKey, $result);
        } else {
            Cache::forever($cacheKey, $result);
        }



        if ($result) {
            $this->indexCacheKey($cacheKey, $result, $foodName, $foods);
        }


        return $result;
    }

    // AiMenuService.php
    public function extractJsonArrayFromResponse(?array $raw): ?array
    {
        if (!is_array($raw)) return null;

        $outputs = \Illuminate\Support\Arr::get($raw, 'output', []);
        if (!is_array($outputs) || empty($outputs)) return null;

        $message = collect($outputs)->firstWhere('type', 'message');
        if (!$message) return null;

        $content = \Illuminate\Support\Arr::get($message, 'content', []);
        if (!is_array($content) || empty($content)) return null;

        // پیدا کردن اولین بخشِ output_text بدون فرض ایندکس
        $text = null;
        foreach ($content as $part) {
            if (($part['type'] ?? null) === 'output_text' && !empty($part['text'])) {
                $text = $part['text'];
                break;
            }
        }
        if (!$text && isset($content[0]['text'])) {
            $text = $content[0]['text'];
        }
        if (!is_string($text) || $text === '') return null;

        $text = $this->cleanJsonString($text);
        $json = json_decode($text, true);

        return is_array($json) ? $json : null;
    }

    /**
     * خروجی «پارس‌شده» (برای پر کردن فرم): ['menu_items','restrictions_about_menu','meals_allowed']
     */
    public function fetchParsed(array $foods, string $foodName, bool $refresh = false): ?array
    {
        $raw = $this->fetchRaw($foods, $foodName, $refresh);
        return $this->parseResponse($raw);
    }

    // ----------------------- Internal helpers -----------------------

    protected function queryBasicFoods(array $foods)
    {
        return BasicFood::query()
            ->where(function ($q) use ($foods) {
                foreach ($foods as $food) {
                    $q->orWhere('name', 'LIKE', "%{$food}%");
                }
            })
            ->get();
    }

    /**
     * کال Responses API با Retry/Timeout از config. خروجی: $response->json()
     */
    protected function callOpenAi(string $prompt, string $foodsJson): ?array
    {
        $apiKey   = (string) (config('ai.openai.api_key') ?: env('OPENAI_API_KEY'));
        $endpoint = (string) (config('ai.openai.endpoint') ?: 'https://api.openai.com/v1/responses');
        $model    = (string) (config('ai.openai.model') ?: 'gpt-5-nano');
        $timeout  = (int)    config('ai.openai.timeout', 0);
        $retries  = (int)    config('ai.openai.retries', 2);
        $sleep    = (int)    config('ai.openai.retry_sleep_ms', 200);

        if (empty($apiKey)) {
            Log::warning('AiMenuService: OPENAI_API_KEY is empty.');
            return null;
        }

        $try = 0;
        do {
            $try++;
            $resp = Http::withToken($apiKey)
                ->asJson()
                ->withOptions([
                    'http_errors'     => false,
                    'connect_timeout' => $timeout,
                    'timeout'         => $timeout,
                    'read_timeout'    => $timeout,
                ])
                ->post($endpoint, [
                    'model' => $model,
                    'input' => [
                        [
                            'role'    => 'system',
                            'content' => 'جواب خروجی برای مشاهده کاربر نیست و دقیقا طبق prompt که میدم جواب بده که میخوام تو کد لاراول استفاده کنم از این جواب '.$foodsJson,
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'stream' => false,
                ]);

            if ($resp->successful()) {
                return $resp->json();
            }

            Log::warning('AiMenuService: OpenAI call failed', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
                'try'    => $try,
            ]);

            if ($try <= $retries) {
                usleep($sleep * 1000);
            }
        } while ($try <= $retries);

        return null;
    }

    /**
     * اگر cache hit بود، بسته به تنظیمات هزینه بگیر یا نگیر.
     */
    protected function maybeChargeOnCacheHit(array $raw, string $prompt): void
    {
        $chargeOnHit = (bool) config('ai.billing.charge_on_cache_hit', false);
        $hitTokens   = (int)  config('ai.billing.cache_hit_tokens', 0);

        if (!$chargeOnHit || $hitTokens <= 0) {
            return; // پیش‌فرض: هزینه صفر روی کش
        }

        // اگر خواستی روی cache hit هم لاگ داشته باشی (با توکن ثابت)
        $userId = Auth::id();
        if (!$userId) return;

        DB::transaction(function () use ($userId, $raw, $prompt, $hitTokens) {
            $aiReq = AiRequest::create([
                'user_id'       => $userId,
                'provider'      => 'openai',
                'model'         => Arr::get($raw, 'model', config('ai.openai.model')),
                'status'        => 'completed',
                'input_tokens'  => 0,
                'output_tokens' => 0,
                'total_tokens'  => $hitTokens,
                'response_id'   => Arr::get($raw, 'id'), // ممکن است نداشته باشد
                'request_meta'  => ['prompt_hash' => sha1($prompt), 'cache_hit' => true],
                'response_meta' => ['note' => 'cache hit billing'],
            ]);

            app(\Modules\AI\Services\TokenService::class)->debitForRequest($aiReq);
        });
    }

    /**
     * ثبت لاگ مصرف و کسر توکن‌ها برای پاسخ‌های موفق واقعی (نه cache hit).
     */
    protected function recordUsageAndDebit(array $json, string $prompt): void
    {
        $userId = Auth::id();
        if (!$userId) {
            // اگر در کانتکست auth نیستی، اینجا می‌تونی userId را تزریق کنی
            return;
        }

        $usage = Arr::get($json, 'usage', []);
        $input  = (int) ($usage['input_tokens']  ?? $usage['input']  ?? 0);
        $output = (int) ($usage['output_tokens'] ?? $usage['output'] ?? 0);
        $total  = (int) ($usage['total_tokens']  ?? ($input + $output));

        DB::transaction(function () use ($userId, $json, $input, $output, $total, $prompt , $usage) {
            $aiReq = AiRequest::create([
                'user_id'       => $userId,
                'provider'      => 'openai',
                'model'         => Arr::get($json, 'model', config('ai.openai.model')),
                'status'        => 'completed',
                'input_tokens'  => $input,
                'output_tokens' => $output,
                'total_tokens'  => $total,
                'response_id'   => Arr::get($json, 'id'),
                'request_meta'  => ['prompt_hash' => sha1($prompt)],
                'response_meta' => ['raw_usage' => $usage],
            ]);

            app(\Modules\AI\Services\TokenService::class)->debitForRequest($aiReq);
        });
    }

    /**
     * پارس استاندارد /v1/responses → استخراج JSON داخل output_text
     * خروجی: ['menu_items'=>[], 'restrictions_about_menu'=>[], 'meals_allowed'=>[]]
     */
    protected function parseResponse(?array $raw): ?array
    {
        if (!is_array($raw)) return null;

        $outputs = Arr::get($raw, 'output', []);
        if (!is_array($outputs) || !$outputs) return null;

        // اولین message
        $message = collect($outputs)->firstWhere('type', 'message') ?: null;
        if (!$message) return null;

        $content = Arr::get($message, 'content', []);
        if (!is_array($content) || !$content) return null;

        // اولین output_text
        $text = null;
        foreach ($content as $part) {
            if (Arr::get($part, 'type') === 'output_text') {
                $text = Arr::get($part, 'text');
                if (is_string($text) && $text !== '') break;
            }
        }
        if (!is_string($text) || $text === '') {
            $text = Arr::get($content, '0.text');
            if (!is_string($text) || $text === '') return null;
        }

        $text = $this->cleanJsonString($text);

        $json = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
            Log::warning('AiMenuService: invalid JSON inside message.text', ['text' => mb_substr($text, 0, 500)]);
            return null;
        }

        return [
            'menu_items'              => Arr::get($json, 'menu_items', []),
            'restrictions_about_menu' => Arr::get($json, 'restrictions_about_menu', []),
            'meals_allowed'           => Arr::get($json, 'meals_allowed', []),
        ];
    }

    /**
     * پاک‌سازی JSON رشته‌ای: حذف ``` ، کوتیشن‌های اضافی، و escapeهای اضافه
     */
    protected function cleanJsonString(string $s): string
    {
        $s = trim($s);

        // ```json ... ```
        if (str_starts_with($s, '```')) {
            $s = preg_replace('/^```[a-zA-Z0-9]*\R?|\R?```$/u', '', $s);
            $s = trim($s);
        }

        // اگر تمام رشته در کوتیشن است
        if ((str_starts_with($s, '"') && str_ends_with($s, '"')) ||
            (str_starts_with($s, "'") && str_ends_with($s, "'"))) {
            $s = substr($s, 1, -1);
            $s = trim($s);
        }

        // بک‌اسلش‌های اضافه
        if (preg_match('/^\{.*\}$/s', $s) === 0 && str_contains($s, '\{')) {
            $tmp = stripslashes($s);
            if (preg_match('/^\{.*\}$/s', $tmp)) {
                $s = $tmp;
            }
        }

        return $s;
    }

    /**
     * قبل از کال: اطمینان از داشتن حداقل توکن (پیش‌فرض 1)
     */
    protected function ensureSufficientTokens(int $minNeeded): void
    {
        if ($minNeeded <= 0) return;

        // عدم وابستگی مستقیم: اگر سرویس موجود نیست، بی‌صدا رد شو
        try {
            app(\Modules\AI\Services\TokenService::class)->ensureSufficient(Auth::id() ?? 0, $minNeeded);
        } catch (\Throwable $e) {
            throw $e; // اجازه بده خطا به بالا بره (HTTP 402 ...)
        }
    }


    private function arrayToPhpArraySyntax(array $data): string
    {
        $lines = [];

        foreach ($data as $key => $value) {
            // رشته رو با کوتیشن و مقدار رو هم بدون کوتیشن بذاریم
            $lines[] =  $key . '" => ' . $value ;
        }


        return implode(PHP_EOL, $lines);
    }
    /**
     * RAW for BasicFood AI import (single item).
     * - Separate cache namespace & index: ai:basic:v1 / ai:basic:index
     * - Prompt key: SettingKeyEnum::AI_PROMPT_BASIC_FOOD (fallback to AI_PROMPT)
     */
    public function fetchRawBasicFood(string $foodName, array $categories , array $units , bool $refresh = false): ?array
    {
        // 0) Ensure minimal tokens
        $minPreTokens = (int) config('ai.billing.min_pre_tokens', 1);
        $this->ensureSufficientTokens($minPreTokens);

        // 1) Prompt (only uses {foodName}; {foods} left empty if present)
        $basePrompt = setting(SettingKeyEnum::AI_PROMPT_BASIC_FOOD);
        $prompt     = str_replace(['{food}', '{categories}' , '{units}'], [$foodName, $this->arrayToPhpArraySyntax($categories) ,$this->arrayToPhpArraySyntax($units)], (string) $basePrompt);

        // 2) Cache key (separate namespace from menu)
        $foodsMaxUpdatedAt = optional(\Modules\Diet\Entities\BasicFood::max('updated_at'), fn($v) => \Carbon\Carbon::parse($v)->timestamp) ?? 0;
        $cacheKey = sprintf('ai:basic:v1:%s:%s', $foodsMaxUpdatedAt, sha1($prompt));

        $cacheEnabled = (bool) config('ai.cache.enabled', true);
        $useTags      = (bool) config('ai.cache.use_tags', false);
        $tag          = 'ai-basic';

        if ($refresh && $cacheEnabled) {
            \Cache::forget($cacheKey);
        }

        // Cache hit
        if ($cacheEnabled && \Cache::has($cacheKey)) {
            $cached = $useTags && method_exists(\Cache::getStore(), 'tags')
                ? \Cache::tags([$tag])->get($cacheKey)
                : \Cache::get($cacheKey);

            if (is_array($cached)) {
                $this->indexCacheKey($cacheKey, $cached, $foodName, []);
                $this->maybeChargeOnCacheHit($cached, $prompt);
                return $cached;
            }
        }

        // Compute
        $compute = function () use ($prompt, $cacheKey) {
            $lock = \Cache::lock('lock:'.$cacheKey, 30);
            try {
                return $lock->block(10, function () use ($prompt) {
                    $raw = $this->callOpenAi($prompt, ''); // no foodsJson
                    if (!$raw || !is_array($raw)) {
                        return null;
                    }
                    $this->recordUsageAndDebit($raw, $prompt);
                    return $raw;
                });
            } finally {
                optional($lock)->release();
            }
        };

        $result = $compute();
        if (!$cacheEnabled || !$result) {
            return $result;
        }

        if ($useTags && method_exists(\Cache::getStore(), 'tags')) {
            \Cache::tags([$tag])->forever($cacheKey, $result);
        } else {
            \Cache::forever($cacheKey, $result);
        }

        if ($result) {
            $this->indexCacheKey($cacheKey, $result, $foodName, []);
        }

        return $result;
    }
}
