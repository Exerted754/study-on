<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private BillingClient $billingClient
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if ($identifier === '') {
            throw new UserNotFoundException();
        }

        $user = new User();
        $user->setEmail($identifier);

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        if (!$this->isTokenExpired($user->getApiToken())) {
            return $user;
        }

        $refreshToken = $user->getRefreshToken();

        if (!$refreshToken) {
            throw new UserNotFoundException('Refresh token not found.');
        }

        try {
            $response = $this->billingClient->refreshToken($refreshToken);
        } catch (BillingUnavailableException) {
            return $user;
        } catch (\Exception) {
            throw new UserNotFoundException('Refresh token is invalid.');
        }

        if (!isset($response['token'])) {
            throw new UserNotFoundException('Token was not refreshed.');
        }

        $user->setApiToken($response['token']);

        if (isset($response['refresh_token'])) {
            $user->setRefreshToken($response['refresh_token']);
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
    }

    private function isTokenExpired(?string $token): bool
    {
        if (!$token) {
            return true;
        }

        $payload = $this->decodeJwtPayload($token);

        if (!isset($payload['exp'])) {
            return false;
        }

        return $payload['exp'] <= time() + 60;
    }

    private function decodeJwtPayload(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return [];
        }

        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);

        $decoded = base64_decode(strtr($payload, '-_', '+/'));

        if ($decoded === false) {
            return [];
        }

        $data = json_decode($decoded, true);

        return is_array($data) ? $data : [];
    }
}
