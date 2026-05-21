<?php

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Length;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a location name.']),
                    new Length(['min' => 3, 'max' => 255]),
                ],
            ])
            ->add('latitude', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 48.8584',
                    'step' => 'any'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Latitude is required.']),
                    new Range([
                        'min' => -90,
                        'max' => 90,
                        'notInRangeMessage' => 'Latitude must be between {{ min }} and {{ max }} degrees.',
                    ]),
                ],
            ])
            ->add('longitude', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 2.2945',
                    'step' => 'any'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Longitude is required.']),
                    new Range([
                        'min' => -180,
                        'max' => 180,
                        'notInRangeMessage' => 'Longitude must be between {{ min }} and {{ max }} degrees.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}