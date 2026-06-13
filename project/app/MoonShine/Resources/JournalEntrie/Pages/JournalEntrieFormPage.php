<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JournalEntrie\Pages;

use App\MoonShine\Resources\Account\AccountResource;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\JournalEntrie\JournalEntrieResource;
use App\TypeEntryEnum;
use Illuminate\Validation\Rule;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Number;

/**
 * @extends FormPage<JournalEntrieResource>
 */
class JournalEntrieFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Number::make("Сумма", "amount")->min(0.01)->step(0.01)->required(),
                Enum::make("Тип", "type")->attach(TypeEntryEnum::class)->required(),
                BelongsTo::make(
                    "Счёт",
                    "account",
                    formatted: static fn ($model) => $model->name,
                    resource: AccountResource::class,
                )->required(),
            ]),
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

    protected function rules(DataWrapperContract $item): array
    {
        return [
            "amount" => ["required", "numeric", "gt:0"],
            "type" => ["required", Rule::enum(TypeEntryEnum::class)],
            "account_id" => ["required", "integer", "exists:accounts,id"],
        ];
    }

    protected function modifyFormComponent(
        FormBuilderContract $component,
    ): FormBuilderContract {
        return $component;
    }

    protected function topLayer(): array
    {
        return [...parent::topLayer()];
    }

    protected function mainLayer(): array
    {
        return [...parent::mainLayer()];
    }

    protected function bottomLayer(): array
    {
        return [...parent::bottomLayer()];
    }
}
