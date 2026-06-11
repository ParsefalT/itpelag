<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction;

use App\Models\Transaction;
use App\MoonShine\Resources\Transaction\Pages\TransactionIndexPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionFormPage;
use App\MoonShine\Resources\Transaction\Pages\TransactionDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\Enums\SortDirection;

/**
 * @extends ModelResource<Transaction, TransactionIndexPage, TransactionFormPage, TransactionDetailPage>
 */

class TransactionResource extends ModelResource
{
    protected string $model = Transaction::class;

    protected string $title = "Transactions";
    protected string $sortColumn = "id";
    protected SortDirection $sortDirection = SortDirection::ASC;
    protected ?PageType $redirectAfterSave = PageType::INDEX;

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
}
