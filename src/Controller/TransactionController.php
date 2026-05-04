<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions')]
#[IsGranted('ROLE_USER')]
class TransactionController extends AbstractController
{
    #[Route('', name: 'app_transaction_index', methods: ['GET'])]
    public function index(BillingClient $billingClient): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $transactions = [];

        try {
            $transactions = $billingClient->getTransactions($user->getApiToken());
        } catch (BillingUnavailableException) {
            $this->addFlash('danger', 'Сервис оплаты временно недоступен');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->render('transaction/index.html.twig', [
            'transactions' => $transactions,
        ]);
    }
}
