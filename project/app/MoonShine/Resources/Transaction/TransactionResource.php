<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction;

use App\Exceptions\PostedTransactionException;
use App\Models\Account;
use App\Models\Transaction;
use App\MoonShine\Resources\BaseResource;
use App\MoonShine\Resources\Transaction\Pages\TransactionDetailPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionFormPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionIndexPage;
use App\Services\LedgerService;
use App\Services\TransactionEntryValidator;
use App\TypeEntryEnum;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\ExportHandler;
use MoonShine\ImportExport\ImportHandler;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Support\Enums\Ability;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<Transaction, TransactionIndexPage, TransactionFormPage, TransactionDetailPage>
 */
class TransactionResource extends BaseResource implements HasImportExportContract
{
    use ImportExportConcern;

    protected string $model = Transaction::class;

    protected string $title = 'Transactions';

    protected string $sortColumn = 'id';

    protected SortDirection $sortDirection = SortDirection::ASC;

    protected ?PageType $redirectAfterSave = PageType::FORM;

    /** @var list<string> */
    protected array $with = ['journalEntries.account'];

    /**
     * @var array<int, list<array{account_id: int, amount: float|int|string, type: string}>>
     */
    private array $pendingImportEntries = [];

    /**
     * @var array<string, int>
     */
    private array $accountIdsByCode = [];

    /**
     * @var array<string, int>
     */
    private array $accountIdsByName = [];

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            TransactionIndexPage::class,
            TransactionFormPage::class,
            TransactionDetailPage::class,
        ];
    }

    protected function afterUpdated(DataWrapperContract $item): DataWrapperContract
    {
        $this->assertBalancedWhenComplete($item);

        return parent::afterUpdated($item);
    }

    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        $this->assertEntriesFromRequest();

        return parent::beforeCreating($item);
    }

    private function assertBalancedWhenComplete(DataWrapperContract $item): void
    {
        $transaction = $item->getOriginal();

        if (! $transaction instanceof Transaction || $transaction->isPosted()) {
            return;
        }

        try {
            app(TransactionEntryValidator::class)->assertBalancedWhenComplete($transaction);
        } catch (\InvalidArgumentException $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $accountId = data_get($this->getFilterParams(), 'account_id')
            ?? data_get($this->getFilterParams(), 'accounts');

        if (filled($accountId)) {
            $builder->whereHas(
                'journalEntries',
                static fn (Builder $query) => $query->where('account_id', $accountId),
            );
        }

        return $builder;
    }

    protected function isCan(Ability $ability): bool
    {
        if (! parent::isCan($ability)) {
            return false;
        }

        if (! in_array($ability, [Ability::UPDATE, Ability::DELETE], true)) {
            return true;
        }

        $transaction = $this->getItem()?->getOriginal();

        return ! ($transaction instanceof Transaction && $transaction->isPosted());
    }

    protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract
    {
        $this->ensureTransactionMutable($item);
        $this->assertEntriesFromRequest();

        return parent::beforeUpdating($item);
    }

    protected function beforeDeleting(DataWrapperContract $item): DataWrapperContract
    {
        $this->ensureTransactionMutable($item);

        return parent::beforeDeleting($item);
    }

    protected function beforeMassDeleting(array $ids): void
    {
        $hasPosted = Transaction::query()
            ->whereIn('id', $ids)
            ->where('is_posted', true)
            ->exists();

        if ($hasPosted) {
            throw new ResourceException(PostedTransactionException::delete()->getMessage());
        }

        parent::beforeMassDeleting($ids);
    }

    private function ensureTransactionMutable(DataWrapperContract $item): void
    {
        $transaction = $item->getOriginal();

        if ($transaction instanceof Transaction && $transaction->isPosted()) {
            throw new ResourceException(PostedTransactionException::modify()->getMessage());
        }
    }

    private function assertEntriesFromRequest(): void
    {
        $entries = request()->input('journalEntries')
            ?? request()->input('journal_entries');

        if (! is_array($entries)) {
            throw new ResourceException('Проводки обязательны для транзакции.');
        }

        $normalized = array_map(
            static fn (array $entry): array => [
                'amount' => $entry['amount'] ?? 0,
                'type' => $entry['type'] ?? '',
            ],
            array_values(array_filter($entries, 'is_array')),
        );

        try {
            app(LedgerService::class)->validateEntries($normalized);
        } catch (\InvalidArgumentException $exception) {
            throw new ResourceException($exception->getMessage());
        }
    }

    protected function import(): ?Handler
    {
        return ImportHandler::make('Импорт')
            ->delimiter(';');
    }

    /**
     * @return ListOf<Handler>
     */
    protected function handlers(): ListOf
    {
        $filename = sprintf('transactions_%s', date('Ymd-His'));

        return new ListOf(Handler::class, array_filter([
            ExportHandler::make('Экспорт Excel')
                ->alias('export-excel')
                ->filename($filename)
                ->icon('document-arrow-down'),
            ExportHandler::make('Экспорт CSV')
                ->alias('export-csv')
                ->csv()
                ->delimiter(';')
                ->filename($filename)
                ->icon('table-cells'),
            $this->import(),
        ]));
    }

    public function beforeImportFilling(array $data): array
    {
        $entries = $data['entries']
            ?? $data['entries_alt']
            ?? $data['journal_entries']
            ?? null;

        if ($entries === null) {
            $entries = $this->buildEntriesFromExport($data);
        }

        if ($entries !== null) {
            $data['_import_entries'] = $entries;
        }

        unset(
            $data['entries'],
            $data['entries_alt'],
            $data['journal_entries'],
            $data['debit_total'],
            $data['credit_total'],
            $data['account_names'],
        );

        return $data;
    }

    public function beforeImported(mixed $item): mixed
    {
        if (! $item instanceof Transaction) {
            return $item;
        }

        $entries = $item->getAttribute('_import_entries');

        if (! is_array($entries) || $entries === []) {
            throw new ResourceException('Проводки обязательны для импорта транзакции.');
        }

        if ($item->exists && $item->isPosted()) {
            $item = $this->freshImportTransaction($item);
        }

        $item->offsetUnset('_import_entries');

        $entries = $this->normalizeImportEntries($entries);

        app(LedgerService::class)->validateEntries($entries);

        $this->pendingImportEntries[spl_object_id($item)] = $entries;

        return $item;
    }

    public function afterImported(mixed $item): mixed
    {
        if (! $item instanceof Transaction) {
            return $item;
        }

        $key = spl_object_id($item);
        $entries = $this->pendingImportEntries[$key] ?? null;

        unset($this->pendingImportEntries[$key]);

        if (! is_array($entries) || $entries === []) {
            return $item;
        }

        DB::transaction(function () use ($item, $entries): void {
            $item->journalEntries()->delete();

            foreach ($entries as $entry) {
                $item->journalEntries()->create([
                    'account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                ]);
            }
        });

        return $item;
    }

    protected function importFields(): iterable
    {
        return [
            ID::make(),
            Date::make('Дата', 'date')->fromRaw(
                fn (mixed $raw): string => $this->normalizeImportDate($raw),
            ),
            Text::make('Описание', 'description'),
            Textarea::make('Проводки (JSON)', 'entries'),
            Textarea::make('Проводки', 'entries_alt'),
            Textarea::make('journal_entries', 'journal_entries'),
            Text::make('Дебет', 'debit_total'),
            Text::make('Кредит', 'credit_total'),
            Text::make('Счета', 'account_names'),
        ];
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make(),
            Date::make('Дата', 'date')->format('d.m.Y'),
            Text::make('Описание', 'description'),
            Text::make('Дебет', 'debit_total')
                ->modifyRawValue(
                    static fn (mixed $raw, mixed $original): string => $original instanceof Transaction
                        ? $original->getSumTotalDebit()
                        : '',
                ),
            Text::make('Кредит', 'credit_total')
                ->modifyRawValue(
                    static fn (mixed $raw, mixed $original): string => $original instanceof Transaction
                        ? $original->getSumTotalCredit()
                        : '',
                ),
            Text::make('Счета', 'account_names')
                ->modifyRawValue(
                    static fn (mixed $raw, mixed $original): string => $original instanceof Transaction
                        ? $original->journalEntries
                            ->pluck('account.name')
                            ->filter()
                            ->unique()
                            ->implode(', ')
                        : '',
                ),
        ];
    }

    private function normalizeImportDate(mixed $raw): string
    {
        if ($raw instanceof \DateTimeInterface) {
            return $raw->format('Y-m-d');
        }

        $value = trim((string) $raw);

        if ($value === '') {
            throw new ResourceException('Дата обязательна для импорта транзакции.');
        }

        $normalized = str_replace('/', '.', $value);

        foreach (['Y-m-d', 'd.m.Y', 'd.m.y', 'Y.m.d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $normalized);

            if ($date instanceof \DateTimeImmutable && $date->format($format) === $normalized) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);

        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        throw new ResourceException('Некорректный формат даты для импорта.');
    }

    /**
     * @param  array<array-key, mixed>|string  $raw
     * @return list<array{account_id: int, amount: float|int|string, type: string}>
     */
    private function normalizeImportEntries(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = trim($raw);

            if ($raw === '') {
                return [];
            }

            try {
                $raw = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new ResourceException('Проводки должны быть JSON массивом.');
            }
        }

        if (! is_array($raw)) {
            throw new ResourceException('Проводки должны быть JSON массивом.');
        }

        $entries = [];

        foreach (array_values($raw) as $entry) {
            if (is_object($entry)) {
                $entry = (array) $entry;
            }

            if (! is_array($entry)) {
                throw new ResourceException('Каждая проводка должна быть объектом.');
            }

            $amount = $entry['amount'] ?? null;

            if ($amount === null || $amount === '') {
                throw new ResourceException('Сумма обязательна для каждой проводки.');
            }

            $entries[] = [
                'account_id' => $this->resolveImportAccountId($entry),
                'amount' => $amount,
                'type' => $this->normalizeImportType($entry['type'] ?? null),
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function resolveImportAccountId(array $entry): int
    {
        $accountId = $entry['account_id'] ?? null;

        if (filled($accountId)) {
            return (int) $accountId;
        }

        $accountCode = $entry['account_code'] ?? null;

        if (! filled($accountCode)) {
            throw new ResourceException('Для каждой проводки нужен account_id или account_code.');
        }

        $code = trim((string) $accountCode);

        if ($code === '') {
            throw new ResourceException('Код счета не может быть пустым.');
        }

        if (! array_key_exists($code, $this->accountIdsByCode)) {
            $id = Account::query()->where('code', $code)->value('id');

            if ($id === null) {
                throw new ResourceException("Счёт с кодом {$code} не найден.");
            }

            $this->accountIdsByCode[$code] = (int) $id;
        }

        return $this->accountIdsByCode[$code];
    }

    private function normalizeImportType(mixed $raw): string
    {
        if ($raw instanceof TypeEntryEnum) {
            return $raw->value;
        }

        $value = strtolower(trim((string) $raw));

        return match ($value) {
            TypeEntryEnum::DEBIT->value, 'дебет' => TypeEntryEnum::DEBIT->value,
            TypeEntryEnum::CREDIT->value, 'кредит' => TypeEntryEnum::CREDIT->value,
            default => throw new ResourceException('Некорректный тип проводки для импорта.'),
        };
    }

    /**
     * @return list<array{account_id: int, amount: float|int|string, type: string}>
     */
    private function buildEntriesFromExport(array $data): ?array
    {
        $debit = $data['debit_total'] ?? null;
        $credit = $data['credit_total'] ?? null;
        $accounts = $data['account_names'] ?? null;

        if (! filled($debit) || ! filled($credit) || ! filled($accounts)) {
            return null;
        }

        $names = $this->parseAccountNames($accounts);

        if ($names === []) {
            throw new ResourceException('Счета обязательны для импорта транзакции.');
        }

        $debitAccountId = $this->resolveImportAccountNameId($names[0]);
        $creditAccountId = $this->resolveImportAccountNameId($names[1] ?? $names[0]);

        return [
            [
                'account_id' => $debitAccountId,
                'amount' => $debit,
                'type' => TypeEntryEnum::DEBIT->value,
            ],
            [
                'account_id' => $creditAccountId,
                'amount' => $credit,
                'type' => TypeEntryEnum::CREDIT->value,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function parseAccountNames(mixed $raw): array
    {
        if (is_string($raw)) {
            $names = array_map('trim', explode(',', $raw));

            return array_values(array_filter($names, static fn (string $name): bool => $name !== ''));
        }

        if (is_array($raw)) {
            $names = array_map(
                static fn (mixed $value): string => trim((string) $value),
                $raw,
            );

            return array_values(array_filter($names, static fn (string $name): bool => $name !== ''));
        }

        return [];
    }

    private function resolveImportAccountNameId(string $name): int
    {
        if (! array_key_exists($name, $this->accountIdsByName)) {
            $id = Account::query()->where('name', $name)->value('id');

            if ($id === null) {
                throw new ResourceException("Счёт с названием {$name} не найден.");
            }

            $this->accountIdsByName[$name] = (int) $id;
        }

        return $this->accountIdsByName[$name];
    }

    private function freshImportTransaction(Transaction $item): Transaction
    {
        $attributes = $item->getAttributes();

        unset(
            $attributes[$item->getKeyName()],
            $attributes['is_posted'],
            $attributes['created_at'],
            $attributes['updated_at'],
            $attributes['_import_entries'],
        );

        $fresh = new Transaction();
        $fresh->forceFill($attributes);

        return $fresh;
    }
}
