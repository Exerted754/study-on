<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    public function __construct()
    {
    }

    public function auth(string $username, string $password): array
    {
        if ($username === 'user@test.local' && $password === 'Topparol') {
            return [
                'token' => $this->createToken(['ROLE_USER'], $username),
            ];
        }

        if ($username === 'admin@test.local' && $password === 'Admin_pass') {
            return [
                'token' => $this->createToken(['ROLE_SUPER_ADMIN'], $username),
            ];
        }

        throw new \Exception('Неверный логин или пароль');
    }

    public function register(string $email, string $password): array
    {
        if ($email === 'exists@test.local') {
            throw new \Exception('Пользователь с таким email уже существует');
        }

        return [
            'token' => $this->createToken(['ROLE_USER'], $email),
        ];
    }

    public function getCurrentUser(string $token): array
    {
        return [
            'username' => 'user@test.local',
            'roles' => ['ROLE_USER'],
            'balance' => 1000,
        ];
    }

    private function createToken(array $roles, string $username): string
    {
        $header = base64_encode(json_encode([
            'typ' => 'JWT',
            'alg' => 'none',
        ]));

        $payload = base64_encode(json_encode([
            'username' => $username,
            'roles' => $roles,
        ]));

        return $header . '.' . $payload . '.signature';
    }
}
