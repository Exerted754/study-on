<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank(message: 'Введите пароль'),
                    new Length(
                        min: 6,
                        minMessage: 'Пароль не должен содержать менее 6 символов'
                    ),
                ],
            ])
            ->add('passwordRepeat', PasswordType::class, [
                'constraints' => [
                    new NotBlank(message: 'Повторите пароль'),
                ],
            ]);
    }
}
