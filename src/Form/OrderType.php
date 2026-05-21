<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\User;
use App\Form\OrderItemType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdAt', null, [
                'widget' => 'single_text',
                'label' => 'Created At',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => Order::STATUS_PENDING,
                    'Completed' => Order::STATUS_COMPLETED,
                    'Cancelled' => Order::STATUS_CANCELLED,
                ],
                'label' => 'Status',
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => [
                    'Cash' => 'cash',
                    'PayPal' => 'paypal',
                ],
                'label' => 'Payment Method',
                'required' => false,
                'placeholder' => 'Select payment method',
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
                'required' => false,
            ])
            ->add('total', NumberType::class, [
                'label' => 'Total',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'readonly' => true,
                    'step' => '0.01',
                    'min' => '0',
                    'class' => 'order-total w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bright-green focus:border-transparent transition-colors duration-200 text-dark-forest-green text-xs',
                ],
            ])
            ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Customer',
                'placeholder' => 'Select a customer',
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $order = $event->getData();
            if (!$order instanceof Order) {
                return;
            }

            $total = '0.00';
            foreach ($order->getItems() as $item) {
                $quantity = $item->getQuantity() ?? 0;
                $unitPrice = $item->getUnitPrice();
                if ($unitPrice === null || $quantity <= 0) {
                    continue;
                }

                $total = bcadd($total, bcmul((string) $unitPrice, (string) $quantity, 2), 2);
            }

            $order->setTotal($total);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
