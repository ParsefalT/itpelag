<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction\Pages;

use App\Models\Account;
use App\MoonShine\Resources\JournalEntrie\JournalEntrieResource;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Transaction\TransactionResource;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use Throwable;

/**
 * @extends FormPage<TransactionResource>
 */
class TransactionFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Date::make("Дата", "date")->required(),
            Text::make("Описание", "description")->required(),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    public function filters(): array
    {
        return [];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            "date" => "required|date",
            "description" => "required|string|max:255",
        ];
    }

    /**
     * @param  FormBuilder  $component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(
        FormBuilderContract $component,
    ): FormBuilderContract {
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
