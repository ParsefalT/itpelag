<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Pages\TrialBalancePage;
use App\MoonShine\Resources\Account\AccountResource;
use App\MoonShine\Resources\JournalEntrie\JournalEntrieResource;
use App\MoonShine\Resources\Transaction\TransactionResource;
use MoonShine\ColorManager\ColorManager;
use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\MenuManager\MenuItem;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = PurplePalette::class;

    protected function assets(): array
    {
        return [...parent::assets()];
    }

    protected function menu(): array
    {
        return [
            ...parent::menu(),
            // MenuItem::make(UserResource::class, 'Users'),
            MenuItem::make(
                AccountResource::class,
                'Accounts',
                'document',
            )->canSee(
                static fn (): bool => request()
                    ->user('moonshine')
                    ?->moonshineUserRole->name == 'Admin',
            ),
            MenuItem::make(
                TransactionResource::class,
                'Transactions',
                'document-check',
            ),
            MenuItem::make(
                JournalEntrieResource::class,
                'JournalEntries',
                'document-text',
            ),
            MenuItem::make(
                TrialBalancePage::class,
                'ОСВ',
                'chart-bar',
            ),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        $colorManager->primary('#00000');
    }
}
