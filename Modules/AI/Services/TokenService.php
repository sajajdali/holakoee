<?php
namespace Modules\AI\Services;

use Illuminate\Support\Facades\Cache;
use Modules\AI\app\Models\AiRequest;
use Modules\AI\app\Models\TokenTransaction;

class TokenService
{
    const CACHE_KEY = 'user:%d:token_balance';
    const CACHE_TTL = 300;

    public function balance(int $userId): int
    {
        $key = sprintf(self::CACHE_KEY, $userId);
        return Cache::remember($key, self::CACHE_TTL, function () use ($userId) {
            return (int) TokenTransaction::where('user_id', $userId)
                ->selectRaw("
                    SUM(
                      CASE
                        WHEN type IN ('credit','refund','adjust') THEN amount
                        ELSE -amount
                      END
                    ) as bal
                ")->value('bal') ?? 0;
        });
    }


    public function credit(int $userId, int $amount, ?string $referenceType = null, ?int $referenceId = null, array $meta = []): TokenTransaction
    {
        $tx = TokenTransaction::create([
            'user_id' => $userId, 'type' => 'credit', 'amount' => $amount,
            'reference_type' => $referenceType, 'reference_id' => $referenceId, 'meta' => $meta
        ]);
        $this->forget($userId);
        return $tx;
    }

    public function debitForRequest(AiRequest $req): ?TokenTransaction
    {
        $amount = (int) $req->total_tokens;
        if ($amount <= 0) return null;

        $tx = TokenTransaction::create([
            'user_id' => $req->user_id, 'type' => 'debit', 'amount' => $amount,
            'reference_type' => AiRequest::class, 'reference_id' => $req->id,
            'meta' => ['model' => $req->model]
        ]);
        $this->forget($req->user_id);
        return $tx;
    }

    public function ensureSufficient(int $userId, int $minNeeded = 1): void
    {
        if ($this->balance($userId) < $minNeeded) {
            abort(402, 'اعتبار توکن شما کافی نیست. لطفاً ابتدا توکن بخرید.');
        }
    }

    private function forget(int $userId): void
    {
        Cache::forget(sprintf(self::CACHE_KEY, $userId));
    }
}
