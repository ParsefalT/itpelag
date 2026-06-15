<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'account_id' => $this->resource['account_id'],
            'code' => $this->resource['code'],
            'name' => $this->resource['name'],
            'type' => $this->resource['type'],
            'debit_total' => $this->resource['debit_total'],
            'credit_total' => $this->resource['credit_total'],
            'balance' => $this->resource['balance'],
        ];
    }
}
