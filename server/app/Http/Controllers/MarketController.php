<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Markets\Enums\ChartDays;
use App\Domain\Markets\Interactors\Coins\GetHistoricalDataInteractor;
use App\Domain\Markets\Interactors\Coins\GetHistoricalDataRequest;
use App\Domain\Markets\Interactors\Coins\GetMarketDataInteractor;
use App\Domain\Markets\Interactors\Coins\GetCoinMarketDataRequest;
use App\Domain\Markets\Interactors\Coins\GetCoinProfileInteractor;
use App\Domain\Markets\Interactors\Coins\GetCoinProfileRequest;
use App\Domain\Markets\Interactors\Coins\GetCoinsInteractor;
use App\Domain\Markets\Interactors\Coins\GetCoinsRequest;
use App\Domain\Markets\Interactors\GlobalStats\GetGlobalStatsInteractor;
use App\Http\ApiResponse;
use App\Http\Requests\Markets\GetCoinHistoricalDataApiRequest;
use App\Http\Requests\Markets\GetCoinsApiRequest;
use App\Http\Resources\Markets\CoinCollectionResource;
use App\Http\Resources\Markets\CoinHistoricalDataCollectionResource;
use App\Http\Resources\Markets\CoinMarketDataResource;
use App\Http\Resources\Markets\CoinProfileResource;
use App\Http\Resources\Markets\GlobalStatsResource;

final class MarketController
{
    public function getGlobalStats(GetGlobalStatsInteractor $globalStatsInteractor): ApiResponse
    {
        $globalStats = $globalStatsInteractor->execute()->globalStats;
        return ApiResponse::success(new GlobalStatsResource($globalStats));
    }

    public function getCoins(
        GetCoinsApiRequest $request,
        GetCoinsInteractor $getCoinsInteractor
    ): ApiResponse {
        $coinsResponse = $getCoinsInteractor->execute(
            new GetCoinsRequest([
                'page' => $request->page(),
                'perPage' => $request->perPage(),
            ])
        );

        return ApiResponse::success(
            new CoinCollectionResource($coinsResponse->coins),
            [
                'total' => $coinsResponse->total,
                'page' => $coinsResponse->page,
                'per_page' => $coinsResponse->perPage,
            ]
        );
    }

    public function getCoinProfile(
        GetCoinProfileInteractor $coinProfileInteractor,
        int $id
    ): ApiResponse {
        $coinProfile = $coinProfileInteractor
            ->execute(new GetCoinProfileRequest(['id' => $id]))
            ->coinProfile;

        return ApiResponse::success(new CoinProfileResource($coinProfile));
    }

    public function getCoinMarketData(
        GetCoinMarketDataInteractor $coinMarketDataInteractor,
        int $id
    ): ApiResponse {
        $coinMarketData = $coinMarketDataInteractor
            ->execute(new GetCoinMarketDataRequest(['id' => $id]))
            ->coinMarketData;

        return ApiResponse::success(new CoinMarketDataResource($coinMarketData));
    }

    public function getCoinHistoricalData(
        GetCoinHistoricalDataApiRequest $request,
        GetHistoricalDataInteractor $historicalDataInteractor
    ): ApiResponse {
        $days = [
            '1d' => ChartDays::ONE_DAY,
            '1w' => ChartDays::ONE_WEEK,
            '1m' => ChartDays::ONE_MONTH,
            '6m' => ChartDays::SIX_MONTH,
            '1y' => ChartDays::ONE_YEAR,
            'all' => ChartDays::MAX,
        ][$request->period()];

        $coinHistoricalData = $historicalDataInteractor
            ->execute(
                new GetHistoricalDataRequest([
                    'id' => $request->id(),
                    'days' => new ChartDays($days),
                ])
            )
            ->historicalData;

        return ApiResponse::success(new CoinHistoricalDataCollectionResource($coinHistoricalData));
    }
}
