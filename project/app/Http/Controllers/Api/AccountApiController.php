<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountBalanceResource;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/v1/accounts",
    operationId: "listAccounts",
    summary: "Список счетов",
    description: "Возвращает id счетов для использования в поле account_id при создании транзакций.",
    security: [["basicAuth" => []]],
    tags: ["Accounts"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Список счетов",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Account"),
                    ),
                ],
            ),
        ),
        new OA\Response(response: 401, description: "Не авторизован"),
    ],
)]
#[OA\Get(
    path: "/v1/accounts/{account}/balance",
    operationId: "getAccountBalance",
    summary: "Остаток по счёту",
    description: "Возвращает обороты по дебету/кредиту и текущий сальдо с учётом типа счёта.",
    security: [["basicAuth" => []]],
    tags: ["Accounts"],
    parameters: [
        new OA\Parameter(name: "account", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Остаток по счёту",
            content: new OA\JsonContent(ref: "#/components/schemas/AccountBalanceResponse"),
        ),
        new OA\Response(response: 401, description: "Не авторизован"),
        new OA\Response(response: 404, description: "Счёт не найден"),
    ],
)]
class AccountApiController extends Controller
{
    public function __construct(private AccountBalanceService $accountBalanceService) {}

    public function index(): AnonymousResourceCollection
    {
        $accounts = Account::query()
            ->where("is_active", true)
            ->orderBy("code")
            ->paginate(50);

        return AccountResource::collection($accounts);
    }

    public function balance(Account $account): JsonResponse
    {
        return response()->json([
            "data" => new AccountBalanceResource(
                $this->accountBalanceService->getBalance($account),
            ),
        ]);
    }
}
