<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
class Transaction extends Model
{
    use HasFactory;
    protected $fillable = ["date", "description"];

    protected $casts = [
        "date" => "date",
    ];

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function accounts(): HasManyThrough
    {
        return $this->hasManyThrough(Account::class, JournalEntry::class);
    }

    // public function getAccountsAttribute()
    // {
    //     return $this->accounts()->all();
    // }
    public function isBalanced(): bool
    {
        $debitTotal = $this->journalEntries()
            ->where("type", "debit")
            ->sum("amount");
        $creditTotal = $this->journalEntries()
            ->where("type", "credit")
            ->sum("amount");

        return bccomp((string) $debitTotal, (string) $creditTotal, 2) === 0;
    }

    public function getSumTotalDebit(): string
    {
        return $this->journalEntries()->where("type", "debit")->sum("amount");
    }

    public function getSumTotalCredit(): string
    {
        return $this->journalEntries()->where("type", "credit")->sum("amount");
    }
}
