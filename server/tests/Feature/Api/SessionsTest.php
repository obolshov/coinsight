<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Users\Models\Session;
use App\Domain\Users\Services\TokenService;
use Illuminate\Http\Response;

final class SessionsTest extends ApiTestCase
{
    public function test_get_sessions()
    {
        $this
            ->apiGet('sessions')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'sessions' => [
                        '*' => $this->sessionStructure(),
                    ],
                ],
                'meta' => $this->metaStructure(),
            ]);
    }

    public function test_refresh()
    {
        $tokenService = $this->app->make(TokenService::class);
        $refreshToken = $tokenService->generateRefreshToken($this->session->id);
        $this->headers['Authorization'] = 'Bearer ' . $refreshToken;

        $this
            ->apiGet('sessions/refresh')
            ->dump()
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'access_token'
                ],
            ]);
    }

    public function test_terminate()
    {
        $session = factory(Session::class)->create();

        $this
            ->apiPut('sessions/terminate', [
                'id' => $session->id,
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'id' => $session->id,
                ],
            ]);
    }

    private function sessionStructure(): array
    {
        return [
            'id',
            'user_id',
            'created_at',
            'last_used_at',
        ];
    }
}
