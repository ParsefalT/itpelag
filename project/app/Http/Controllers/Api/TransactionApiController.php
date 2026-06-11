<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionApiController extends Controller
{
    public function __construct(private LedgerService $ledgerService) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with("journalEntries.account")
            ->latest("date")
            ->paginate(5);

        return response()->json($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $transaction = $this->ledgerService->createTransaction(
                $request->validated(["date", "description"]),
                $request->validated("entries"),
            );

            return response()->json(
                [
                    "message" => "Транзакция успешно создана",
                    "data" => $transaction,
                ],
                201,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(["error" => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json([
            "data" => $transaction->load("journalEntries.account"),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        Transaction $transaction,
    ): JsonResponse {
        try {
            $updated = $this->ledgerService->updateTransaction(
                $transaction,
                $request->validated(["date", "description"]),
                $request->validated("entries"),
            );

            return response()->json([
                "message" => "Транзакция успешно обновлена",
                "data" => $updated,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(["error" => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json(["message" => "Транзакция удалена"]);
    }
}
