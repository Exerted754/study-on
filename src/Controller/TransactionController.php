<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions')]
#[IsGranted('ROLE_USER')]
class TransactionController extends AbstractController
{
     #[Route('', name: 'app_transaction_index', methods: ['GET'])]
    public function index(
        Request $request,
        BillingClient $billingClient,
        CourseRepository $courseRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $filters = [
            'type' => $request->query->get('type') ?: null,
            'course_code' => $request->query->get('course_code') ?: null,
            'skip_expired' => $request->query->getBoolean('skip_expired') ?: null,
        ];

        $filters = array_filter($filters, static fn ($value) => $value !== null);

        $transactions = [];

        try {
            $transactions = $billingClient->getTransactions($user->getApiToken(), $filters);
        } catch (BillingUnavailableException) {
            $this->addFlash('danger', 'Сервис оплаты временно недоступен');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        $coursesByCode = [];

        foreach ($courseRepository->findAll() as $course) {
            $coursesByCode[$course->getCode()] = $course;
        }

        return $this->render('transaction/index.html.twig', [
            'transactions' => $transactions,
            'coursesByCode' => $coursesByCode,
            'filters' => [
                'type' => $request->query->get('type'),
                'course_code' => $request->query->get('course_code'),
                'skip_expired' => $request->query->getBoolean('skip_expired'),
            ],
        ]);
    }
}
