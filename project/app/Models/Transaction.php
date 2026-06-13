<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ["date", "description", "is_posted"];

    protected $casts = [
        "date" => "date",
        "is_posted" => "boolean",
    ];

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function accounts(): HasManyThrough
    {
        return $this->hasManyThrough(Account::class, JournalEntry::class);
    }

    public function isPosted(): bool
    {
        return (bool) $this->is_posted;
    }

    public function shouldBePosted(): bool
    {
        return $this->journalEntries()->count() >= 2 && $this->isBalanced();
    }

    public function isBalanced(): bool
    {
        $debitTotal = $this->sumAmountByType("debit");
        $creditTotal = $this->sumAmountByType("credit");

        return $debitTotal === $creditTotal;
    }

    public function getSumTotalDebit(): string
    {
        return Money::fromCents($this->sumAmountByType("debit"));
    }

    public function getSumTotalCredit(): string
    {
        return Money::fromCents($this->sumAmountByType("credit"));
    }

    private function sumAmountByType(string $type): int
    {
        $sum = $this->journalEntries()->where("type", $type)->sum("amount");

        return Money::toCents($sum);
    }
}
