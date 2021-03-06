<?php

declare(strict_types=1);

namespace App\Coinfo;

use App\Coinfo\Aggregators\CoinGecko;
use App\Coinfo\Aggregators\Messari;
use App\Coinfo\Enums\Interval;
use App\Coinfo\Types\CoinMarketDataCollection;
use App\Coinfo\Types\CoinProfile;
use App\Coinfo\Types\CoinHistoricalDataCollection;
use App\Coinfo\Types\NewsArticleCollection;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class Client
{
    private CoinGecko $coinGecko;
    private Messari $messari;

    public function __construct(
        CoinGecko $coinGecko,
        Messari $messari
    ) {
        $this->coinGecko = $coinGecko;
        $this->messari = $messari;
    }

    public function markets(int $page = 1, int $perPage = 100): CoinMarketDataCollection
    {
        return $this->coinGecko->coinsMarkets($page, $perPage, [], false, true);
    }

    public function marketsForCoins(array $currenciesNames): CoinMarketDataCollection
    {
        $slugged = array_map(
            fn(string $currencyName) => Str::slug($currencyName),
            $currenciesNames
        );
        return $this->coinGecko->coinsMarkets(0, 0, $slugged, true);
    }

    public function coinProfile(string $currencyName): CoinProfile
    {
        return $this->messari->assetProfile(Str::slug($currencyName));
    }

    public function coinHistoricalData(string $coinGeckoId, int $days): CoinHistoricalDataCollection {
        return $this->coinGecko->coinMarketChart($coinGeckoId, $days);
    }

    public function coinHistoricalDataAllTime(string $coinGeckoId): CoinHistoricalDataCollection
    {
        return $this->coinGecko->coinMarketChart($coinGeckoId, 'max');
    }

    public function news(int $page = 1): NewsArticleCollection
    {
        return $this->messari->news($page);
    }
}
