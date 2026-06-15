<?php

namespace App\Models;

use App\TypeEntryEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $account_id
 * @property string $amount
 * @property TypeEntryEnum|string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Transaction|null $transaction
 * @property Account|null $account
 *
 * @method static self create(array $attributes = [])
 */
class JournalEntry extends Model
{
    use HasFactory;
    protected $fillable = ['transaction_id', 'account_id', 'amount', 'type'];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TypeEntryEnum::class,
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
