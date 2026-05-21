<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Repository\CourseRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class LessonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminUpload = $options['enable_content_upload'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'placeholder' => 'Lesson title',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'required' => !$adminUpload,
                'attr' => [
                    'rows' => 12,
                    'placeholder' => $adminUpload
                        ? 'Type here, or drop a .twig / .html file. For PDF, drop the file in this area.'
                        : 'Lesson body',
                ],
            ]);

        if ($adminUpload) {
            $builder->add('contentUpload', FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '15M',
                    ]),
                ],
                'attr' => [
                    'accept' => '.twig,.html,.htm,.pdf',
                    'class' => 'hidden',
                ],
            ]);
        }

        $builder
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'courseName',
                'placeholder' => 'Select a course',
                'required' => false,
                'query_builder' => fn (CourseRepository $r) => $r->createQueryBuilder('c')
                    ->orderBy('c.courseName', 'ASC'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'enable_content_upload' => false,
        ]);
        $resolver->setAllowedTypes('enable_content_upload', 'bool');
    }
}
