<?php

namespace App\Models;

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
        return $this->formatAmount(
            (float) $this->journalEntries()->where("type", "debit")->sum("amount"),
        );
    }

    public function getSumTotalCredit(): string
    {
        return $this->formatAmount(
            (float) $this->journalEntries()->where("type", "credit")->sum("amount"),
        );
    }

    private function sumAmountByType(string $type): int
    {
        return (int) round(
            ((float) $this->journalEntries()->where("type", $type)->sum("amount")) * 100,
        );
    }

    private function formatAmount(float $value): string
    {
        return number_format($value, 2, ".", "");
    }
}
