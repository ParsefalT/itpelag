<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction\Pages;

use App\Models\Account;
use App\MoonShine\Resources\Transaction\TransactionResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use Throwable;

/**
 * @extends IndexPage<TransactionResource>
 */
class TransactionIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Date::make('Дата', 'date')->required()->format('d.m.Y'),
            Text::make('Описание', 'description')->required(),
            Text::make('Статус', 'is_posted')
                ->modifyRawValue(
                    static fn (bool $value): string => $value ? 'Проведена' : 'Черновик',
                ),
        ];
    }
    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            DateRange::make('Дата', 'date'),
            Select::make('Счёт', 'account_id')
                ->options(fn () => Account::pluck('name', 'id')->toArray())
                ->onApply(static function ($query, $value) {
                    if (! filled($value)) {
                        return $query;
                    }

                    if (is_array($value)) {
                        $query->whereHas(
                            'journalEntries',
                            static fn ($relationQuery) => $relationQuery->whereIn('account_id', $value),
                        );

                        return $query;
                    }

                    $query->whereHas(
                        'journalEntries',
                        static fn ($relationQuery) => $relationQuery->where('account_id', $value),
                    );

                    return $query;
                }),
        ];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(
        ComponentContract $component,
    ): ComponentContract {
        return $component->columnSelection()->sticky()->stickyButtons();
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
