<?php

namespace App\Models;

use App\TypeEntryEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JournalEntry extends Model
{
    use HasFactory;
    protected $fillable = ["transaction_id", "account_id", "amount", "type"];

    protected $casts = [
        "amount" => "decimal:2",
        "type" => TypeEntryEnum::class,
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
