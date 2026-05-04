<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, BillingClient $billingClient): Response
    {
        $billingCourse = null;
        $hasCourseAccess = false;

        try {
            $billingCourse = $billingClient->getCourse($course->getCode());

            if ($this->getUser() instanceof User) {
                /** @var User $user */
                $user = $this->getUser();

                $hasCourseAccess = $billingClient->hasCourseAccess(
                    $course->getCode(),
                    $user->getApiToken()
                );
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
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
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
