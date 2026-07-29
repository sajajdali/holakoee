<?php
return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'endpoint' => 'https://api.openai.com/v1/responses',
        'model' => env('OPENAI_RESPONSES_MODEL', 'gpt-5-nano'),
        'timeout' => 0,
        'retries' => 2,
        'retry_sleep_ms' => 200,
    ],
    'cache' => [
        'enabled' => true,
        'use_tags' => false,
        'tag' => 'ai-menu',
    ],
    'billing' => [
        // حداقل موجودی برای اجازه شروع درخواست
        'min_pre_tokens' => 1,

        // در cache hit آیا هزینه بگیریم؟
        'charge_on_cache_hit' => false,
        // اگر بگیریم، چند توکن کسر شود؟
        'cache_hit_tokens' => 0,
    ],
];
