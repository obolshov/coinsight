<?php

declare(strict_types=1);

namespace App\Domain\Portfolios\Interactors\Transactions;

use App\Domain\Portfolios\Entities\Transaction as TransactionEntity;
use App\Domain\Portfolios\Models\Transaction;
use App\Domain\Portfolios\Services\FinanceCalculator;
use App\Domain\Portfolios\Services\TransactionService;

final class GetTransactionsInteractor
{
    private FinanceCalculator $calculator;
    private TransactionService $transactionService;

    public function __construct(
        FinanceCalculator $financeCalculator,
        TransactionService $transactionService
    ) {
        $this->calculator = $financeCalculator;
        $this->transactionService = $transactionService;
    }

    public function execute(GetTransactionsRequest $request): GetTransactionsResponse
    {
        $transactionsPaginator = $this->transactionService->paginateByPortfolioIdAndUserId(
            $request->portfolioId,
            $request->userId,
            $request->page,
            $request->perPage,
            $request->sort,
            $request->direction,
            ['coin', 'coin.marketData']
        );

        if ($transactionsPaginator->isEmpty()) {
            return new GetTransactionsResponse([
                'transactions' => collect(),
                'total' => $transactionsPaginator->total(),
                'page' => $transactionsPaginator->currentPage(),
                'perPage' => $transactionsPaginator->perPage(),
                'lastPage' => $transactionsPaginator->lastPage(),
            ]);
        }

        $transactionEntityCollection = $transactionsPaginator->map(
            function (Transaction $transaction) {
                $cost = $this->calculator->cost(
                    $transaction->quantity, $transaction->price_per_coin, $transaction->fee
                );
                $currentValue = $this->calculator->value(
                    $transaction->quantity, $transaction->coin->marketData->price
                );
                $valueChange = $this->calculator->valueChange($currentValue, $cost);

                $transactionEntity = TransactionEntity::fromModel($transaction);
                $transactionEntity->cost = $cost;
                $transactionEntity->currentValue = $currentValue;
                $transactionEntity->valueChange = $valueChange;

                return $transactionEntity;
            }
        );

        return new GetTransactionsResponse([
            'transactions' => $transactionEntityCollection,
            'total' => $transactionsPaginator->total(),
            'page' => $transactionsPaginator->currentPage(),
            'perPage' => $transactionsPaginator->perPage(),
            'lastPage' => $transactionsPaginator->lastPage(),
        ]);
    }
}
