<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TrialBalanceService
{
    /**
     * @return list<array<string, string>>
     */
    public function build(string $from, string $to): array
    {
        $rows = DB::table("accounts")
            ->leftJoin("journal_entries as je", "je.account_id", "=", "accounts.id")
            ->leftJoin("transactions as t", static function ($join): void {
                $join->on("t.id", "=", "je.transaction_id")
                    ->where("t.is_posted", true);
            })
            ->where("accounts.is_active", true)
            ->groupBy("accounts.id", "accounts.code", "accounts.name")
            ->orderBy("accounts.code")
            ->selectRaw(
                "
                accounts.id,
                accounts.code,
                accounts.name,
                COALESCE(SUM(CASE WHEN t.date < ? AND je.type = 'debit' THEN je.amount ELSE 0 END), 0) as opening_debit_raw,
                COALESCE(SUM(CASE WHEN t.date < ? AND je.type = 'credit' THEN je.amount ELSE 0 END), 0) as opening_credit_raw,
                COALESCE(SUM(CASE WHEN t.date >= ? AND t.date <= ? AND je.type = 'debit' THEN je.amount ELSE 0 END), 0) as turnover_debit_raw,
                COALESCE(SUM(CASE WHEN t.date >= ? AND t.date <= ? AND je.type = 'credit' THEN je.amount ELSE 0 END), 0) as turnover_credit_raw,
                COALESCE(SUM(CASE WHEN t.date <= ? AND je.type = 'debit' THEN je.amount ELSE 0 END), 0) as closing_debit_raw,
                COALESCE(SUM(CASE WHEN t.date <= ? AND je.type = 'credit' THEN je.amount ELSE 0 END), 0) as closing_credit_raw
            ",
                [$from, $from, $from, $to, $from, $to, $to, $to],
            )
            ->get()
            ->map(fn (object $row): array => $this->mapRow($row))
            ->filter(fn (array $row): bool => $this->hasActivity($row))
            ->values()
            ->all();

        if ($rows === []) {
            return [];
        }

        $rows[] = $this->buildTotalsRow($rows);

        return $rows;
    }

    private function mapRow(object $row): array
    {
        [$openingDebit, $openingCredit] = $this->splitBalance(
            $this->formatAmount($row->opening_debit_raw),
            $this->formatAmount($row->opening_credit_raw),
        );

        [$closingDebit, $closingCredit] = $this->splitBalance(
            $this->formatAmount($row->closing_debit_raw),
            $this->formatAmount($row->closing_credit_raw),
        );

        return [
            "code" => (string) $row->code,
            "name" => (string) $row->name,
            "opening_debit" => $openingDebit,
            "opening_credit" => $openingCredit,
            "turnover_debit" => $this->formatAmount($row->turnover_debit_raw),
            "turnover_credit" => $this->formatAmount($row->turnover_credit_raw),
            "closing_debit" => $closingDebit,
            "closing_credit" => $closingCredit,
            "is_total" => "0",
        ];
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array<string, string>
     */
    private function buildTotalsRow(array $rows): array
    {
        $totals = Collection::make($rows)->reduce(
            fn (array $carry, array $row): array => [
                "opening_debit" => $this->add($carry["opening_debit"], $row["opening_debit"]),
                "opening_credit" => $this->add($carry["opening_credit"], $row["opening_credit"]),
                "turnover_debit" => $this->add($carry["turnover_debit"], $row["turnover_debit"]),
                "turnover_credit" => $this->add($carry["turnover_credit"], $row["turnover_credit"]),
                "closing_debit" => $this->add($carry["closing_debit"], $row["closing_debit"]),
                "closing_credit" => $this->add($carry["closing_credit"], $row["closing_credit"]),
            ],
            [
                "opening_debit" => "0.00",
                "opening_credit" => "0.00",
                "turnover_debit" => "0.00",
                "turnover_credit" => "0.00",
                "closing_debit" => "0.00",
                "closing_credit" => "0.00",
            ],
        );

        return [
            "code" => "",
            "name" => "Итого",
            "opening_debit" => $totals["opening_debit"],
            "opening_credit" => $totals["opening_credit"],
            "turnover_debit" => $totals["turnover_debit"],
            "turnover_credit" => $totals["turnover_credit"],
            "closing_debit" => $totals["closing_debit"],
            "closing_credit" => $totals["closing_credit"],
            "is_total" => "1",
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitBalance(string $debitSum, string $creditSum): array
    {
        $diff = $this->toCents($debitSum) - $this->toCents($creditSum);

        if ($diff > 0) {
            return [$this->fromCents($diff), "0.00"];
        }

        if ($diff < 0) {
            return ["0.00", $this->fromCents(abs($diff))];
        }

        return ["0.00", "0.00"];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function hasActivity(array $row): bool
    {
        foreach ([
            "opening_debit",
            "opening_credit",
            "turnover_debit",
            "turnover_credit",
            "closing_debit",
            "closing_credit",
        ] as $column) {
            if ($this->toCents($row[$column]) !== 0) {
                return true;
            }
        }

        return false;
    }

    private function formatAmount(mixed $value): string
    {
        return $this->fromCents((int) round(((float) $value) * 100));
    }

    private function add(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) + $this->toCents($right));
    }

    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, ".", "");
    }
}
