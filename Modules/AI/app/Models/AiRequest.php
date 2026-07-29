<?php

namespace Modules\AI\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Entities\User;

class AiRequest extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'status',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'response_id',
        'request_meta',
        'response_meta',
    ];

    protected $casts = [
        'request_meta' => 'array',
        'response_meta' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->hasOne(TokenTransaction::class, 'reference_id')
            ->where('reference_type', self::class);
    }
}
