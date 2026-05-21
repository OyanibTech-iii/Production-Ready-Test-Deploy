<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use App\Constants\RegistrationMessageKeys;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'constraints' => [
                    new NotBlank([
                        'message' => RegistrationMessageKeys::FIRSTNAME_BLANK,
                        'groups' => null,
                    ]),
                    new Length([
                        'max' => 100,
                    ]),
                    new Regex([
                        'pattern' => '/[a-zA-Z]/u',
                        'message' => RegistrationMessageKeys::FIRSTNAME_INVALID,
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'constraints' => [
                    new NotBlank([
                        'message' => RegistrationMessageKeys::LASTNAME_BLANK,
                        'groups' => null,
                    ]),
                    new Length([
                        'max' => 100,
                    ]),
                    new Regex([
                        'pattern' => '/[a-zA-Z]/u',
                        'message' => RegistrationMessageKeys::LASTNAME_INVALID,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => RegistrationMessageKeys::EMAIL_BLANK,
                    ]),
                    new Length([
                        'max' => 180,
                    ]),
                    new Email([
                        'message' => RegistrationMessageKeys::EMAIL_INVALID,
                    ])
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => RegistrationMessageKeys::AGREE_TERMS,
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => RegistrationMessageKeys::PASSWORD_BLANK,
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => RegistrationMessageKeys::PASSWORD_TOO_SHORT,
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
