<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Account;

use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use App\MoonShine\Resources\Account\Pages\AccountIndexPage;
use App\MoonShine\Resources\Account\Pages\AccountFormPage;
use App\MoonShine\Resources\Account\Pages\AccountDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Models\MoonshineUser;
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
}
