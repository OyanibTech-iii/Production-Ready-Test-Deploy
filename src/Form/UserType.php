<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;


class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter email address'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Email should not be blank.',
                    ]),
                    new Email([
                        'message' => 'The email "{{ value }}" is not a valid email.',
                    ])
                ],
            ])
            ->add('firstName', TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter first name'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'First name should not be blank.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'First name cannot be longer than {{ limit }} characters.',
                        'min' => 2,
                        'minMessage' => 'First name must be at least {{ limit }} characters long.',
                    ]),
                    new Regex([
                        'pattern' => '/[a-zA-Z]/u',
                        'message' => 'First name contains invalid characters.',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter last name'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Last name should not be blank.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Last name cannot be longer than {{ limit }} characters.',
                        'min' => 2,
                        'minMessage' => 'Last name must be at least {{ limit }} characters long.',
                    ]),
                    new Regex([
                        'pattern' => '/[a-zA-Z]/u',
                        'message' => 'Last name contains invalid characters.',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter phone number'
                ],
               ' constraints' => [
                    new Length([
                        'max' => 11,
                        'maxMessage' => 'Phone number cannot be longer than {{ limit }} characters.',
                        'min' => 11,
                        'minMessage' => 'Phone number must be at least {{ limit }} characters long.',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9+\-\s()]*$/',
                        'message' => 'Phone number contains invalid characters.',
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'required' => $options['is_edit'] ? false : true,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Enter password'
                ],
                'constraints' => [ 
                    new NotBlank([
                        'message' => 'Password should not be blank.',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Password must be at least {{ limit }} characters long.',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
                    ]),
                ],            
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Staff' => 'ROLE_STAFF',
                    'Admin' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'flex flex-wrap gap-4'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-4 h-4 text-bright-green bg-gray-100 border-gray-300 rounded focus:ring-bright-green'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
