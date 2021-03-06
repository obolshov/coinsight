<?php

declare(strict_types=1);

namespace App\Http\Markets\Requests;

use App\Http\Common\Requests\ApiRequest;
use App\Http\Common\Requests\AuthContextTrait;

final class GetCoinsApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1|max:100',
        ];
    }

    public function page(): ?int
    {
        return (int) $this->get('page');
    }

    public function perPage(): ?int
    {
        return (int) $this->get('per_page');
    }
}
