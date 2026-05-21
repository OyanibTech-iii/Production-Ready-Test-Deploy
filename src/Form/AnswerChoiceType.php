<?php

namespace App\Form;

use App\Entity\AnswerChoice;
use App\Entity\Question;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnswerChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('choiceText')
            ->add('isCorrect')
            ->add('question', EntityType::class, [
                'class' => Question::class,
                'choice_label' => function (Question $question) {
                    return sprintf('%d - %s', $question->getId(), $question->getQuestionText());
                },
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnswerChoice::class,
        ]);
    }
}
