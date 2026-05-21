<?php

namespace App\Form;

use App\Entity\Certificate;
use App\Entity\Course;
use App\Entity\QuizAttempt;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CertificateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $randomString = bin2hex(random_bytes(12));
        $defaultCode = 'GrowficoOfficial' . $randomString;
        $builder
            ->add('certificateCode', null, [
                'data' => $options['data']->getId() ? $options['data']->getCertificateCode() : $defaultCode,
                'attr' => [
                    'readonly' => true, 
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bright-green focus:border-transparent transition-colors duration-200 text-dark-forest-green'
                ]
            ])->add('issuedAt', null, [
                    'widget' => 'single_text'
                ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return sprintf('%d - %s %s', $user->getId(), $user->getFirstName(), $user->getLastName());
                },
            ])
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => function (Course $course) {
                    return sprintf('%d - %s', $course->getId(), $course->getCourseName());
                },
            ])
            ->add('quizAttempt', EntityType::class, [
                'class' => QuizAttempt::class,
                'choice_label' => function (QuizAttempt $qa) {
                    return sprintf(
                        'ID: %d | %s (Score: %d)',
                        $qa->getId(),
                        $qa->getQuiz()->getTitle(),
                        $qa->getScore()
                    );
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Certificate::class,
        ]);
    }
}
