<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('RoOLE_USER')]
    public function index(BillingClient $billingClient): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $billingClient = $billingClient->getCurrentUser($user->getApiToken());
            $balance = $billingClient['balance'] ?? null;
        } catch (BillingUnavailableException) {
            $balance = null;

            $this->addFlash(
                'danger',
                'Сервис временно недоступен. Попробуйте открыть профиль позднее!',
            );
        } catch (\Exception $exception) {
            $balance = null;

            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->render('profile/index.html.twig', [
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'balance' => $balance,
        ]);
    }
}
