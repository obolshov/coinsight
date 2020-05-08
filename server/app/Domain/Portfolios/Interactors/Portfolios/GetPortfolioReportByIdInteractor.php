<?php

declare(strict_types=1);

namespace App\Domain\Portfolios\Interactors\Portfolios;

use App\Coinfo\Client;
use App\Domain\Markets\Entities\Coin;
use App\Domain\Portfolios\Entities\Asset;
use App\Domain\Portfolios\Entities\Portfolio;
use App\Domain\Portfolios\Entities\Report;
use App\Domain\Portfolios\Entities\ValueByTime;
use App\Domain\Portfolios\Enums\TransactionType;
use App\Domain\Portfolios\Services\PortfolioService;
use App\Domain\Portfolios\Services\FinanceCalculator;

final class GetPortfolioReportByIdInteractor
{
    private PortfolioService $portfolioService;
    private Client $client;
    private FinanceCalculator $calculator;

    public function __construct(
        PortfolioService $portfolioService,
        Client $client,
        FinanceCalculator $financeCalculator
    ) {
        $this->portfolioService = $portfolioService;
        $this->client = $client;
        $this->calculator = $financeCalculator;
    }

    public function execute(GetPortfolioReportByIdRequest $request): GetPortfolioReportByIdResponse
    {
        $portfolio = $this->portfolioService->getByIdAndUserId(
            $request->portfolioId, $request->userId, ['transactions', 'transactions.coin']
        );

        $portfolioCoins = [];

        foreach($portfolio->transactions as $transaction) {
            $portfolioCoins[$transaction->coin->name][] = $transaction;
        }

        $coinsMarketOverview = collect($this->client->marketsForCoins(array_keys($portfolioCoins)));

        $portfolioTotalValue = 0;
        $portfolioTotalCost = 0;
        $assets = collect();
        $assetsValuesByTime = [];

        foreach ($portfolioCoins as $coinName => $transactions) {
            $coinMarketOverview = $coinsMarketOverview->firstWhere('name', $coinName);

            $assetMarketValue = 0;
            $assetNetCost = 0;
            $assetHoldings = 0;

            foreach ($transactions as $transaction) {
                if ($transaction->type === TransactionType::BUY) {
                    $assetMarketValue += $this->calculator->value(
                        $transaction->quantity, $coinMarketOverview->price
                    );
                    $assetNetCost += $this->calculator->cost(
                        $transaction->quantity, $transaction->price_per_coin, $transaction->fee
                    );
                    $assetHoldings += $transaction->quantity;
                } else {
                    $assetMarketValue -= $this->calculator->value(
                        $transaction->quantity, $coinMarketOverview->price
                    );
                    $assetNetCost -= $this->calculator->cost(
                        $transaction->quantity, $transaction->price_per_coin, $transaction->fee
                    );
                    $assetHoldings -= $transaction->quantity;
                }
            }

            $assetNetProfit = $this->calculator->netProfit($assetMarketValue, $assetNetCost);
            $assetValueChange = $this->calculator->valueChange($assetMarketValue, $assetNetCost);

            $portfolioTotalValue += $assetMarketValue;
            $portfolioTotalCost += $assetNetCost;

            $coin = $transactions[0]->coin;

            $assets[] = new Asset([
                'coin' => Coin::fromModel($coin),
                'price' => $coinMarketOverview->price,
                'priceChange24h' => $coinMarketOverview->priceChange24h,
                'holdings' => $assetHoldings,
                'marketValue' => $assetMarketValue,
                'netCost' => $assetNetCost,
                'netProfit' => $assetNetProfit,
                'valueChange' => $assetValueChange
            ]);

            $assetsValuesByTime[] = $this->getAssetValueByTime(
                $assetHoldings, $coinMarketOverview->sparkline
            );
        }

        $portfolioValueByTime = $this->getPortfolioValueByTime($assetsValuesByTime);

        foreach ($assets as $asset) {
            $asset->share = $asset->holdings > 0
                ? $this->calculator->share($asset->marketValue, $portfolioTotalValue)
                : 0;
        }

        $portfolioValueChange = $this->calculator->valueChange($portfolioTotalValue, $portfolioTotalCost);

        $portfolioReport = new Report([
            'portfolio' => Portfolio::fromModel($portfolio),
            'totalValue' => $portfolioTotalValue,
            'totalValueChange' => $portfolioValueChange,
            'assets' => $assets,
            'valueByTime' => collect($portfolioValueByTime),
        ]);

        return new GetPortfolioReportByIdResponse([
            'report' => $portfolioReport,
        ]);
    }

    private function getAssetValueByTime(float $holdings, array $sparkline): array
    {
        $priceByTime = [];
        $dateStart = now()->subDays(7);

        for ($i = 0; $i < count($sparkline); $i++) {
            $priceByTime[] = new ValueByTime([
                'datetime' => $dateStart->clone()->addHours($i),
                'value' => $sparkline[$i],
            ]);
        }

        $valueByTime = [];

        foreach ($priceByTime as $item) {
            $valueByTime[] = new ValueByTime([
                'datetime' => $item->datetime,
                'value' => $holdings * $item->value,
            ]);
        }

        return $valueByTime;
    }

    private function getPortfolioValueByTime(array $assetsValueByTime): array
    {
        $portfolioValueByTime = [];
        for ($i = 0; $i < count($assetsValueByTime[0]); $i++) {
            $portfolioValue = 0;
            for ($j = 0; $j < count($assetsValueByTime); $j++) {
                $portfolioValue += $assetsValueByTime[$j][$i]->value;
            }

            $portfolioValueByTime[] = new ValueByTime([
                'datetime' => $assetsValueByTime[0][$i]->datetime,
                'value' => $portfolioValue,
            ]);
        }
        return $portfolioValueByTime;
    }
}
