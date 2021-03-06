<?php

declare(strict_types=1);

namespace App\Domain\Portfolios\Interactors\Transactions;

use App\Domain\Common\Contracts\PaginationRequest;
use Spatie\DataTransferObject\DataTransferObject;

final class GetTransactionsRequest extends DataTransferObject implements PaginationRequest
{
    public int $userId;
    public int $portfolioId;
    public int $page = self::DEFAULT_PAGE;
    public int $perPage = self::DEFAULT_PER_PAGE;
    public string $sort = self::DEFAULT_SORT;
    public string $direction = self::DEFAULT_DIRECTION;
}
