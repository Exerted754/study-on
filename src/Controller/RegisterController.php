<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Form\RegisterType;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppCustomAuthenticator;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        BillingClient $billingClient,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($data['password'] !== $data['passwordRepeat']) {
                $form->get('passwordRepeat')->addError(
                    new FormError('Пароли не совпадают')
                );

                return $this->render('registration/register.html.twig', [
                    'form' => $form,
                ]);
            }

            try {
                $response = $billingClient->register($data['email'], $data['password']);
            } catch (BillingUnavailableException) {
                $form->addError(new FormError(
                    'Сервис временно недоступен. Попробуйте зарегистрироваться позднее'
                ));

                return $this->render('registration/register.html.twig', [
                    'form' => $form,
                ]);
            } catch (\Exception $exception) {
                $form->addError(new FormError($exception->getMessage()));

                return $this->render('registration/register.html.twig', [
                    'form' => $form,
                ]);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles($response['roles'] ?? ['ROLE_USER']);
            $user->setApiToken($response['token']);

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }
}
