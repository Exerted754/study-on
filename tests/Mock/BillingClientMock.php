<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    public function __construct(
        private array $closedCourses = []
    ) {
    }

    public function auth(string $username, string $password): array
    {
        if ($username === 'user@test.local' && $password === 'Topparol') {
            return [
                'token' => $this->createToken(['ROLE_USER'], $username),
                'refresh_token' => 'user_refresh_token',
            ];
        }

        if ($username === 'admin@test.local' && $password === 'Admin_pass') {
            return [
                'token' => $this->createToken(['ROLE_SUPER_ADMIN'], $username),
                'refresh_token' => 'admin_refresh_token',
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
            'refresh_token' => 'register_refresh_token',
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
            'exp' => time() + 3600,
        ]));

        return $header . '.' . $payload . '.signature';
    }

    public function refreshToken(string $refreshToken): array
    {
        if ($refreshToken === 'admin_refresh_token') {
            return [
                'token' => $this->createToken(['ROLE_SUPER_ADMIN'], 'admin@test.local'),
                'refresh_token' => 'admin_refresh_token',
            ];
        }

        return [
            'token' => $this->createToken(['ROLE_USER'], 'user@test.local'),
            'refresh_token' => 'user_refresh_token',
        ];
    }

    public function getCourses(): array
    {
        return [
            [
                'code' => 'php-basic',
                'type' => 'buy',
                'price' => 199.99,
            ],
            [
                'code' => 'symfony-start',
                'type' => 'rent',
                'price' => 99.99,
            ],
            [
                'code' => 'postgresql-base',
                'type' => 'free',
            ],
        ];
    }

    public function getCourse(string $code): array
    {
        foreach ($this->getCourses() as $course) {
            if ($course['code'] === $code) {
                return $course;
            }
        }

        throw new \Exception('Курс не найден.');
    }

    public function payCourse(string $code, string $token): array
    {
        $course = $this->getCourse($code);

        $response = [
            'success' => true,
            'course_type' => $course['type'],
        ];

        if ($course['type'] === 'rent') {
            $response['expires_at'] = (new \DateTimeImmutable('+1 week'))->format(DATE_ATOM);
        }

        return $response;
    }

    public function getTransactions(string $token, array $filters = []): array
    {
        $transactions = [
            [
                'id' => 1,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'type' => 'deposit',
                'amount' => 1000,
            ],
            [
                'id' => 2,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'type' => 'payment',
                'amount' => 199.99,
                'course_code' => 'php-basic',
            ],
            [
                'id' => 3,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'type' => 'payment',
                'amount' => 99.99,
                'course_code' => 'symfony-start',
                'expires_at' => (new \DateTimeImmutable('+1 week'))->format(DATE_ATOM),
            ],
        ];

        if (!empty($filters['type'])) {
            $transactions = array_filter($transactions, static function (array $transaction) use ($filters) {
                return $transaction['type'] === $filters['type'];
            });
        }

        if (!empty($filters['course_code'])) {
            $transactions = array_filter($transactions, static function (array $transaction) use ($filters) {
                return ($transaction['course_code'] ?? null) === $filters['course_code'];
            });
        }

        if (!empty($filters['skip_expired'])) {
            $transactions = array_filter($transactions, static function (array $transaction) {
                if (!isset($transaction['expires_at'])) {
                    return true;
                }

                return new \DateTimeImmutable($transaction['expires_at']) > new \DateTimeImmutable();
            });
        }

        return array_values($transactions);
    }

    public function hasCourseAccess(string $courseCode, string $token): bool
    {
        if (in_array($courseCode, $this->closedCourses, true)) {
            return false;
        }

        return in_array($courseCode, [
            'php-basic',
            'symfony-start',
            'postgresql-base',
        ], true);
    }
}
