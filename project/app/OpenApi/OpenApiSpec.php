<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'ITPelag Ledger API',
    version: '1.0.0',
    description: 'REST API учётной системы ITPelag. Создание транзакций с двойной записью и получение остатков по счетам.',
)]
#[OA\Server(
    url: '/api',
    description: 'Базовый URL API',
)]
#[OA\SecurityScheme(
    securityScheme: 'basicAuth',
    type: 'http',
    scheme: 'basic',
    description: 'HTTP Basic Authentication. Используйте email и пароль пользователя Laravel.',
)]
#[OA\Tag(name: 'Transactions', description: 'Операции с транзакциями и проводками')]
#[OA\Tag(name: 'Accounts', description: 'Остатки по счетам')]
#[OA\Schema(
    schema: 'Account',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 21),
        new OA\Property(property: 'code', type: 'string', example: '128'),
        new OA\Property(property: 'name', type: 'string', example: 'Блинчики'),
        new OA\Property(property: 'type', type: 'string', example: 'asset'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'JournalEntryInput',
    required: ['account_id', 'amount', 'type'],
    properties: [
        new OA\Property(
            property: 'account_id',
            type: 'integer',
            description: 'ID счёта из GET /v1/accounts (не код счёта).',
            example: 21,
        ),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 250.00),
        new OA\Property(property: 'type', type: 'string', enum: ['debit', 'credit'], example: 'debit'),
    ],
)]
#[OA\Schema(
    schema: 'StoreTransactionRequest',
    required: ['date', 'description', 'entries'],
    properties: [
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-06-11'),
        new OA\Property(property: 'description', type: 'string', example: 'Оплата по счету'),
        new OA\Property(
            property: 'entries',
            type: 'array',
            minItems: 2,
            items: new OA\Items(ref: '#/components/schemas/JournalEntryInput'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'JournalEntry',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'account_id', type: 'integer', example: 1),
        new OA\Property(property: 'account_name', type: 'string', example: 'Касса'),
        new OA\Property(property: 'amount', type: 'string', example: '250.00'),
        new OA\Property(property: 'type', type: 'string', enum: ['debit', 'credit'], example: 'debit'),
    ],
)]
#[OA\Schema(
    schema: 'Transaction',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-06-11'),
        new OA\Property(property: 'description', type: 'string', example: 'Оплата по счету'),
        new OA\Property(property: 'is_posted', type: 'boolean', example: true),
        new OA\Property(
            property: 'journal_entries',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/JournalEntry'),
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'TransactionResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Транзакция успешно создана'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
    ],
)]
#[OA\Schema(
    schema: 'TransactionItemResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
    ],
)]
#[OA\Schema(
    schema: 'AccountBalance',
    properties: [
        new OA\Property(property: 'account_id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: '128'),
        new OA\Property(property: 'name', type: 'string', example: 'Касса'),
        new OA\Property(property: 'type', type: 'string', example: 'asset'),
        new OA\Property(property: 'debit_total', type: 'string', example: '250.00'),
        new OA\Property(property: 'credit_total', type: 'string', example: '0.00'),
        new OA\Property(property: 'balance', type: 'string', example: '250.00'),
    ],
)]
#[OA\Schema(
    schema: 'AccountBalanceResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/AccountBalance'),
    ],
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Сумма дебета (100.00) должна быть равна сумме кредита (50.00).'),
    ],
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The date field is required.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ),
    ],
)]
#[OA\Schema(
    schema: 'MessageResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Транзакция удалена'),
    ],
)]
final class OpenApiSpec
{
}
