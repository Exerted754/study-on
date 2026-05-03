<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;

class BillingClient
{
    public function __construct(
        private string $billingApiUrl
    ) {
    }

    public function auth(string $username, string $password): array
    {
        return $this->request(
            'POST',
            '/api/v1/auth',
            [
                'username' => $username,
                'password' => $password,
            ]
        );
    }

    public function getCurrentUser(string $token): array
    {
        return $this->request(
            'GET',
            '/api/v1/users/current',
            null,
            $token,
        );
    }

    public function register(string $email, string $password): array
    {
        return $this->request(
            'POST',
            '/api/v1/register',
            [
                'email' => $email,
                'password' => $password,
            ]
        );
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->request(
            'POST',
            '/api/v1/token/refresh',
            [
                'refresh_token' => $refreshToken,
            ]
        );
    }

    private function request(
        string $method,
        string $uri,
        ?array $data = null,
        ?string $token = null
    ): array {
        $ch = curl_init($this->billingApiUrl . $uri);

        $headers = [
            'Content-Type: application/json',
        ];

        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        // exception
        if ($data !== null) {
           curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);

            throw new BillingUnavailableException($error ?: 'Billing unavailable');
        }


        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new BillingUnavailableException('Billing returned invalid response');
        }

        if ($statusCode >= 400) {
            throw new \Exception($decoded['message'] ?? 'Billing error', $statusCode);
        }

        return $decoded;
    }
}
