<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\PostedTransactionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTransactionRequest;
use App\Http\Requests\Api\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/v1/transactions',
    operationId: 'listTransactions',
    summary: 'Список транзакций',
    security: [['basicAuth' => []]],
    tags: ['Transactions'],
    responses: [
        new OA\Response(response: 200, description: 'Список транзакций с пагинацией'),
        new OA\Response(response: 401, description: 'Не авторизован'),
    ],
)]
#[OA\Post(
    path: '/v1/transactions',
    operationId: 'createTransaction',
    summary: 'Создать транзакцию',
    security: [['basicAuth' => []]],
    tags: ['Transactions'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreTransactionRequest'),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Транзакция создана',
            content: new OA\JsonContent(ref: '#/components/schemas/TransactionResponse'),
        ),
        new OA\Response(
            response: 422,
            description: 'Ошибка валидации или несбалансированные проводки',
            content: new OA\JsonContent(oneOf: [
                new OA\Schema(ref: '#/components/schemas/ErrorResponse'),
                new OA\Schema(ref: '#/components/schemas/ValidationErrorResponse'),
            ]),
        ),
        new OA\Response(response: 401, description: 'Не авторизован'),
    ],
)]
#[OA\Get(
    path: '/v1/transactions/{transaction}',
    operationId: 'showTransaction',
    summary: 'Получить транзакцию',
    security: [['basicAuth' => []]],
    tags: ['Transactions'],
    parameters: [
        new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Транзакция',
            content: new OA\JsonContent(ref: '#/components/schemas/TransactionItemResponse'),
        ),
        new OA\Response(response: 401, description: 'Не авторизован'),
        new OA\Response(response: 404, description: 'Не найдено'),
    ],
)]
#[OA\Put(
    path: '/v1/transactions/{transaction}',
    operationId: 'updateTransaction',
    summary: 'Обновить транзакцию',
    description: 'Доступно только для непроведённых транзакций.',
    security: [['basicAuth' => []]],
    tags: ['Transactions'],
    parameters: [
        new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreTransactionRequest'),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Транзакция обновлена',
            content: new OA\JsonContent(ref: '#/components/schemas/TransactionResponse'),
        ),
        new OA\Response(
            response: 422,
            description: 'Ошибка валидации или транзакция проведена',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
        ),
        new OA\Response(response: 401, description: 'Не авторизован'),
        new OA\Response(response: 404, description: 'Не найдено'),
    ],
)]
#[OA\Delete(
    path: '/v1/transactions/{transaction}',
    operationId: 'deleteTransaction',
    summary: 'Удалить транзакцию',
    description: 'Доступно только для непроведённых транзакций.',
    security: [['basicAuth' => []]],
    tags: ['Transactions'],
    parameters: [
        new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Транзакция удалена',
            content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
        ),
        new OA\Response(
            response: 422,
            description: 'Транзакция проведена',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
        ),
        new OA\Response(response: 401, description: 'Не авторизован'),
        new OA\Response(response: 404, description: 'Не найдено'),
    ],
)]
class TransactionApiController extends Controller
{
    public function __construct(private LedgerService $ledgerService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $transactions = Transaction::query()
            ->with('journalEntries.account')
            ->latest('date')
            ->paginate(15);

        return TransactionResource::collection($transactions);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $transaction = $this->ledgerService->createTransaction(
                [
                    'date' => $validated['date'],
                    'description' => $validated['description'],
                ],
                $validated['entries'],
            );

            return response()->json(
                [
                    'message' => 'Транзакция успешно создана',
                    'data' => new TransactionResource(
                        $transaction->load('journalEntries.account'),
                    ),
                ],
                201,
            );
        } catch (\InvalidArgumentException|PostedTransactionException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json([
            'data' => new TransactionResource(
                $transaction->load('journalEntries.account'),
            ),
        ]);
    }

    public function update(
        UpdateTransactionRequest $request,
        Transaction $transaction,
    ): JsonResponse {
        try {
            $validated = $request->validated();

            $updated = $this->ledgerService->updateTransaction(
                $transaction,
                [
                    'date' => $validated['date'],
                    'description' => $validated['description'],
                ],
                $validated['entries'],
            );

            return response()->json([
                'message' => 'Транзакция успешно обновлена',
                'data' => new TransactionResource(
                    $updated->load('journalEntries.account'),
                ),
            ]);
        } catch (\InvalidArgumentException|PostedTransactionException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        try {
            $this->ledgerService->deleteTransaction($transaction);

            return response()->json(['message' => 'Транзакция удалена']);
        } catch (PostedTransactionException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
