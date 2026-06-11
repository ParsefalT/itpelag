<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JournalEntrie\Pages;

use App\MoonShine\Resources\Account\AccountResource;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\JournalEntrie\JournalEntrieResource;
use App\MoonShine\Resources\Transaction\TransactionResource;
use App\TypeEntryEnum;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use Throwable;

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
                Text::make("Amount", "amount")->required(),
                Enum::make("Type", "type")->attach(TypeEntryEnum::class),

                BelongsTo::make(
                    "account_id",
                    "account",
                    null,
                    AccountResource::class,
                ),
                BelongsTo::make(
                    "transaction_id",
                    "transaction",
                    null,
                    TransactionResource::class,
                ),
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
        return [];
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
