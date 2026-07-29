<?php

namespace Modules\AI\Services\Traits;

use Illuminate\Support\Facades\Cache;

trait CashAiTrait
{
    protected function cacheIndexKey(): string
    {
        return 'ai:menu:index'; // یک آرایه از [{key, title, ts}] نگه می‌داریم
    }

    /**
     * افزودن/به‌روزرسانی ایتم ایندکس کش
     */
    protected function indexCacheKey(string $cacheKey, array $raw, string $foodName, array $foods): void
    {
        // سعی می‌کنیم عنوان معنادار استخراج کنیم:
        $title = $this->extractMenuTitle($raw) ?: ($foodName ?: implode(' + ', $foods));
        $max   = (int) config('ai.cache.index_max', 200); // حداکثر تعداد ایتم در ایندکس
        $nowTs = now()->timestamp;

        $idxKey = $this->cacheIndexKey();
        $list   = Cache::get($idxKey, []);

        // اگر موجود است، حذف قبلی تا تکراری نشود
        $list = array_values(array_filter($list, fn($i) => ($i['key'] ?? '') !== $cacheKey));

        // اول لیست اضافه‌اش کن (آخرش هم می‌توانیم trim کنیم)
        array_unshift($list, [
            'key'   => $cacheKey,
            'title' => $title ?: 'بدون عنوان',
            'ts'    => $nowTs,
        ]);

        // محدود کردن طول
        if (count($list) > $max) {
            $list = array_slice($list, 0, $max);
        }

        Cache::put($idxKey, $list, now()->addDays(14)); // ایندکس هم TTL داشته باشد
    }

    /**
     * تلاش برای استخراج عنوان معنی‌دار از خروجی خام مدل
     */
    protected function extractMenuTitle(?array $raw): ?string
    {
        if (!is_array($raw)) return null;

        $outputs = \Illuminate\Support\Arr::get($raw, 'output', []);
        if (!is_array($outputs) || empty($outputs)) return null;

        $message = collect($outputs)->firstWhere('type', 'message');
        if (!$message) return null;

        $content = \Illuminate\Support\Arr::get($message, 'content', []);
        if (!is_array($content) || empty($content)) return null;

        $text = null;
        foreach ($content as $part) {
            if (($part['type'] ?? null) === 'output_text' && !empty($part['text'])) {
                $text = $part['text'];
                break;
            }
        }
        if (!$text) $text = $content[0]['text'] ?? null;
        if (!is_string($text) || $text === '') return null;

        $text = $this->cleanJsonString($text);
        $json = json_decode($text, true);
        if (!is_array($json)) return null;

        // اگر prompt شما فیلدی مثل menu_name می‌سازد:
        return $json['menu_name'] ?? null;
    }

}
