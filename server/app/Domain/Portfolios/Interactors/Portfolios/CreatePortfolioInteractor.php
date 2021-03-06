<?php

declare(strict_types=1);

namespace App\Domain\Portfolios\Interactors\Portfolios;

use App\Domain\Portfolios\Entities\Portfolio as PortfolioEntity;
use App\Domain\Portfolios\Models\Portfolio;
use App\Domain\Users\Models\User;

final class CreatePortfolioInteractor
{
    public function execute(CreatePortfolioRequest $request): CreatePortfolioResponse
    {
        $user = User::findOrFail($request->userId, ['id']);

        $portfolio = new Portfolio();
        $portfolio->name = $request->name;
        $portfolio->user_id = $user->id;

        $portfolio->save();
        $portfolio->refresh();

        return new CreatePortfolioResponse([
            'portfolio' => PortfolioEntity::fromModel($portfolio)
        ]);
    }
}
