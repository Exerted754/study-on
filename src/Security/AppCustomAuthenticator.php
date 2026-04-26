<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private BillingClient $billingClient
    ) {
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $email = $request->getPayload()->getString('email');
        $password = $request->getPayload()->getString('password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        try {
            $response = $this->billingClient->auth($email, $password);
        } catch (BillingUnavailableException) {
            throw new CustomUserMessageAuthenticationException(
                'Сервис временно недоступен. Попробуйте авторизоваться позднее'
            );
        } catch (\Exception $exception) {
            throw new CustomUserMessageAuthenticationException(
                $exception->getMessage()
            );
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email, $response) {
                $user = new User();

                $token = $response['token'];
                $payload = $this->decodeJwtPayload($token);

                $user->setEmail($email);
                $user->setRoles($payload['roles'] ?? ['ROLE_USER']);
                $user->setApiToken($token);

                return $user;
            }),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    private function decodeJwtPayload(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return [];
        }

        $payload = $parts[1];

        $payload .= str_repeat('=', 4 - strlen($payload) % 4);

        $decoded = base64_decode(strtr($payload, '-_', '+/'));

        if ($decoded === false) {
            return [];
        }

        $data = json_decode($decoded, true);

        return is_array($data) ? $data : [];
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
