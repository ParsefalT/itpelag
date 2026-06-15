<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction\Pages;

use App\MoonShine\Resources\JournalEntrie\JournalEntrieResource;
use App\MoonShine\Resources\Transaction\TransactionResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Text;
use Throwable;

/**
 * @extends DetailPage<TransactionResource>
 */
class TransactionDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Text::make('Дата', 'date'),
            Text::make('Описание', 'description'),
            Text::make('Статус', 'is_posted')
                ->modifyRawValue(
                    static fn (bool $value): string => $value ? 'Проведена' : 'Черновик',
                ),
            HasMany::make(
                'Проводки',
                'journalEntries',
                null,
                JournalEntrieResource::class,
            )->creatable(false),
        ];
    }

    protected function filters(): iterable
    {
        return [Text::make('Type', 'journalEntries')];
    }

    public function getTitle(): string
    {
        return __('Transaction');
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(
        ComponentContract $component,
    ): ComponentContract {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [...parent::topLayer()];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [...parent::mainLayer()];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [...parent::bottomLayer()];
    }
}
