<?php

declare(strict_types=1);

namespace App\Domain\Portfolios\Interactors\Portfolios;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\DataTransferObject;

final class GetPortfolioAssetsByIdResponse extends DataTransferObject
{
    public Collection $assets;
    public int $total;
    public int $page;
    public int $perPage;
    public int $lastPage;
}