<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction;

use App\Models\Transaction;
use App\MoonShine\Resources\Transaction\Pages\TransactionDetailPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionFormPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionIndexPage;
use App\Exceptions\PostedTransactionException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\ExportHandler;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Ability;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<Transaction, TransactionIndexPage, TransactionFormPage, TransactionDetailPage>
 */
class TransactionResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern;

    protected string $model = Transaction::class;

    protected string $title = "Transactions";

    protected string $sortColumn = "id";

    protected SortDirection $sortDirection = SortDirection::ASC;

    protected ?PageType $redirectAfterSave = PageType::INDEX;

    /** @var list<string> */
    protected array $with = ["journalEntries.account"];

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

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $accountId = data_get($this->getFilterParams(), "account_id")
            ?? data_get($this->getFilterParams(), "accounts");

        if (filled($accountId)) {
            $builder->whereHas(
                "journalEntries",
                static fn (Builder $query) => $query->where("account_id", $accountId),
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
            ->whereIn("id", $ids)
            ->where("is_posted", true)
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

    protected function import(): ?Handler
    {
        return null;
    }

    /**
     * @return ListOf<Handler>
     */
    protected function handlers(): ListOf
    {
        $filename = sprintf("transactions_%s", date("Ymd-His"));

        return new ListOf(Handler::class, [
            ExportHandler::make("Экспорт Excel")
                ->alias("export-excel")
                ->filename($filename)
                ->icon("document-arrow-down"),
            ExportHandler::make("Экспорт CSV")
                ->alias("export-csv")
                ->csv()
                ->delimiter(";")
                ->filename($filename)
                ->icon("table-cells"),
        ]);
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make(),
            Date::make("Дата", "date")->format("d.m.Y"),
            Text::make("Описание", "description"),
            Text::make("Дебет", "debit_total")
                ->modifyRawValue(
                    static fn (mixed $raw, mixed $original): string => $original instanceof Transaction
                        ? $original->getSumTotalDebit()
                        : "",
                ),
            Text::make("Кредит", "credit_total")
                ->modifyRawValue(
                    static fn (mixed $raw, mixed $original): string => $original instanceof Transaction
                        ? $original->getSumTotalCredit()
                        : "",
                ),
            Text::make("Счета", "account_names")
                ->modifyRawValue(
                    static fn (mixed $raw, mixed $original): string => $original instanceof Transaction
                        ? $original->journalEntries
                            ->pluck("account.name")
                            ->filter()
                            ->unique()
                            ->implode(", ")
                        : "",
                ),
        ];
    }
}
