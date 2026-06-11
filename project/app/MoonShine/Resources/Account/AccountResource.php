<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Account;

use App\Exceptions\AccountInUseException;
use App\Models\Account;
use App\MoonShine\Resources\Account\Pages\AccountDetailPage;
use App\MoonShine\Resources\Account\Pages\AccountFormPage;
use App\MoonShine\Resources\Account\Pages\AccountIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Ability;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\Enums\SortDirection;

/**
 * @extends ModelResource<Account, AccountIndexPage, AccountFormPage, AccountDetailPage>
 */
class AccountResource extends ModelResource
{
    protected string $model = Account::class;

    protected string $title = "Accounts";

    protected string $column = "name";

    protected string $sortColumn = "id";

    protected SortDirection $sortDirection = SortDirection::ASC;

    protected ?PageType $redirectAfterSave = PageType::INDEX;

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            AccountIndexPage::class,
            AccountFormPage::class,
            AccountDetailPage::class,
        ];
    }

    protected function isCan(Ability $ability): bool
    {
        if (! parent::isCan($ability)) {
            return false;
        }

        if ($ability !== Ability::DELETE) {
            return true;
        }

        $account = $this->getItem()?->getOriginal();

        return ! ($account instanceof Account && $this->hasJournalEntries($account));
    }

    protected function beforeDeleting(DataWrapperContract $item): DataWrapperContract
    {
        $this->ensureAccountDeletable($item);

        return parent::beforeDeleting($item);
    }

    protected function beforeMassDeleting(array $ids): void
    {
        $hasEntries = Account::query()
            ->whereIn("id", $ids)
            ->whereHas("journalEntries")
            ->exists();

        if ($hasEntries) {
            throw new ResourceException(AccountInUseException::delete()->getMessage());
        }

        parent::beforeMassDeleting($ids);
    }

    private function ensureAccountDeletable(DataWrapperContract $item): void
    {
        $account = $item->getOriginal();

        if ($account instanceof Account && $this->hasJournalEntries($account)) {
            throw new ResourceException(AccountInUseException::delete()->getMessage());
        }
    }

    private function hasJournalEntries(Account $account): bool
    {
        return $account->journalEntries()->exists();
    }
}
