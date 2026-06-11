<?php

namespace App\Models;

use App\TypeAccountEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;
    protected $fillable = ["name", "code", "type", "is_active"];

    protected $casts = [
        "is_active" => "boolean",
        "type" => TypeAccountEnum::class,
    ];

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function scopeActiveAccount(Builder $query): Builder
    {
        return $query->where("is_active", true);
    }

    public function getAmountSumAttribute()
    {
        return $this->journalEntries()->sum("amount");
    }
}
