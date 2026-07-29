<?php

namespace Modules\AI\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\AI\Database\factories\TokenTransactionFactory;
use Modules\User\Entities\User;

class TokenTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
