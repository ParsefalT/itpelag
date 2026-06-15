<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Resources\Account\AccountResource;
use App\MoonShine\Resources\JournalEntrie\JournalEntrieResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\Transaction\TransactionResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                UserResource::class,
                AccountResource::class,
                TransactionResource::class,
                JournalEntrieResource::class,
            ])
            ->pages([...$core->getConfig()->getPages()]);
    }
}
