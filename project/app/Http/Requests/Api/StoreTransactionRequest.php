<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\TypeEntryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'entries.*.amount' => ['required', 'numeric', 'gt:0'],
            'entries.*.type' => ['required', Rule::enum(TypeEntryEnum::class)],
        ];
    }
}
