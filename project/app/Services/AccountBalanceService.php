<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\TypeAccountEnum;

final class AccountBalanceService
{
    /**
     * @return array{
     *     account_id: int,
     *     code: string,
     *     name: string,
     *     type: string,
     *     debit_total: string,
     *     credit_total: string,
     *     balance: string
     * }
     */
    public function getBalance(Account $account): array
    {
        $debitCents = $this->sumByType($account, "debit");
        $creditCents = $this->sumByType($account, "credit");

        $balanceCents = $this->isDebitNormal($account->type)
            ? $debitCents - $creditCents
            : $creditCents - $debitCents;

        return [
            "account_id" => $account->id,
            "code" => $account->code,
            "name" => $account->name,
            "type" => $account->type->value,
            "debit_total" => $this->fromCents($debitCents),
            "credit_total" => $this->fromCents($creditCents),
            "balance" => $this->fromCents($balanceCents),
        ];
    }

    private function sumByType(Account $account, string $type): int
    {
        return (int) round(
            ((float) $account->journalEntries()->where("type", $type)->sum("amount")) * 100,
        );
    }

    private function isDebitNormal(TypeAccountEnum $type): bool
    {
        return in_array($type, [TypeAccountEnum::ASSET, TypeAccountEnum::EXPENSE], true);
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, ".", "");
    }
}
