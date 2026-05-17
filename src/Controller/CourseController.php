<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courses')]
final class CourseController extends AbstractController
{
    #[Route(name: 'app_course_index', methods: ['GET'])]
        public function index(
        CourseRepository $courseRepository,
        BillingClient $billingClient
    ): Response {
        $billingCourses = [];

        try {
            foreach ($billingClient->getCourses() as $billingCourse) {
                $billingCourses[$billingCourse['code']] = $billingCourse;
            }
        } catch (\Exception) {
            $this->addFlash('danger', 'Не удалось получить данные о стоимости курсов');
        }

        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
            'billingCourses' => $billingCourses,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        BillingClient $billingClient
    ): Response {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            $billingType = $form->get('billingType')->getData();
            $billingPrice = $form->get('billingPrice')->getData();

            try {
                $billingClient->createCourse(
                    $course->getCode(),
                    $course->getTitle(),
                    $billingType,
                    $billingPrice !== null ? (float) $billingPrice : null,
                    $user->getApiToken()
                );
            } catch (BillingUnavailableException) {
                $form->addError(new FormError('Сервис оплаты временно недоступен'));
            } catch (\Exception $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }

            if (count($form->getErrors(true)) === 0) {
                $entityManager->persist($course);
                $entityManager->flush();

                return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ], new Response(
            null,
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK
        ));
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, BillingClient $billingClient): Response
    {
        $billingCourse = null;
        $hasCourseAccess = false;
        $balance = null;

        try {
            $billingCourse = $billingClient->getCourse($course->getCode());

            if ($this->getUser() instanceof User) {
                /** @var User $user */
                $user = $this->getUser();

                $hasCourseAccess = $billingClient->hasCourseAccess(
                    $course->getCode(),
                    $user->getApiToken()
                );

                $billingUser = $billingClient->getCurrentUser($user->getApiToken());
                $balance = $billingUser['balance'] ?? null;
            }

            if (($billingCourse['type'] ?? null) === 'free') {
                $hasCourseAccess = true;
            }
        } catch (\Exception) {
            $this->addFlash('danger', 'Не удалось получить данные о стоимости курса');
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'billingCourse' => $billingCourse,
            'hasCourseAccess' => $hasCourseAccess,
            'balance' => $balance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager,
        BillingClient $billingClient
    ): Response {
        $oldCode = $course->getCode();

        $form = $this->createForm(CourseType::class, $course);

        if (!$request->isMethod('POST')) {
            try {
                $billingCourse = $billingClient->getCourse($course->getCode());

                $form->get('billingType')->setData($billingCourse['type'] ?? 'free');
                $form->get('billingPrice')->setData($billingCourse['price'] ?? null);
            } catch (\Exception) {
                $this->addFlash('danger', 'Не удалось получить данные о стоимости курса');
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            $billingType = $form->get('billingType')->getData();
            $billingPrice = $form->get('billingPrice')->getData();

            try {
                $billingClient->updateCourse(
                    $oldCode,
                    $course->getCode(),
                    $course->getTitle(),
                    $billingType,
                    $billingPrice !== null ? (float) $billingPrice : null,
                    $user->getApiToken()
                );
            } catch (BillingUnavailableException) {
                $form->addError(new FormError('Сервис оплаты временно недоступен'));
            } catch (\Exception $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }

            if (count($form->getErrors(true)) === 0) {
                $entityManager->flush();

                return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ], new Response(
            null,
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK
        ));
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pay', name: 'app_course_pay', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function pay(Course $course, BillingClient $billingClient): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $response = $billingClient->payCourse($course->getCode(), $user->getApiToken());

            if (($response['course_type'] ?? null) === 'rent') {
                $this->addFlash('success', 'Курс успешно арендован');
            } else {
                $this->addFlash('success', 'Курс успешно оплачен');
            }
        } catch (BillingUnavailableException) {
            $this->addFlash('danger', 'Сервис оплаты временно недоступен');
        } catch (\Exception $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('app_course_show', [
            'id' => $course->getId(),
        ]);
    }
}
