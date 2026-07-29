<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Coupon\Entities\Coupon;
use Modules\Transaction\Enum\TransactionPaidEnum;
use Modules\Transaction\Enum\TransactionPaymentForEnum;
use Modules\Transaction\Enum\TransactionStatusEnum;
use Modules\User\Entities\User;

/**
 * Modules\Transaction\Entities\Transaction
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $coupon_id
 * @property string|null $transaction_code
 * @property TransactionPaymentForEnum $payment_for 10 = diet | 20 = exercise | 30 = both them
 * @property TransactionStatusEnum $status 1 = success | 2 = pending | 0 = reject
 * @property TransactionPaidEnum $paid_by 1 = online | 2 card to card | 3 = by admin
 * @property int $cost
 * @property int $total_cost
 * @property int $discount
 * @property array|null $detail
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Coupon|null $coupon
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePaidBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePaymentFor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTransactionCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'paid_by'     => TransactionPaidEnum::class ,
        'payment_for' => TransactionPaymentForEnum::class,
        'status'      => TransactionStatusEnum::class ,
        'detail'      => 'json' ,
    ] ;

    public static function generateTransactionCode(): string
    {
        do {
            $uniqueCode = static::generateUniqueCode();
        } while (static::where('transaction_code', $uniqueCode)->exists());

        // Insert the unique code into the "transaction" table
        return $uniqueCode;
    }

    protected static function generateUniqueCode()
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        // Generate a random code
        for ($i = 0; $i < 4; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $code;
    }

    // Function to generate a unique code and insert a new transaction

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
       return  $this->belongsTo(User::class);
    }
    public function coupon(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return  $this->belongsTo(Coupon::class);
    }


    // Function to generate a unique code

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
