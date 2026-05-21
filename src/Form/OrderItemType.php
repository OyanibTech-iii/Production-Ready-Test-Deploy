<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderItemType extends AbstractType
{
    public function __construct(private ProductRepository $productRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function (Product $product) {
                    return sprintf('%s (%d in stock)', $product->getName(), $product->getCurrentStockQuantity());
                },
                'query_builder' => function (ProductRepository $productRepository) {
                    return $productRepository->createQueryBuilder('p')
                        ->innerJoin('p.stocks', 's')
                        ->andWhere('p.isAvailable = :available')
                        ->setParameter('available', true)
                        ->groupBy('p.id')
                        ->having('SUM(s.Quantity) > 0')
                        ->orderBy('p.name', 'ASC');
                },
                'choice_attr' => function (Product $product) {
                    return [
                        'data-price' => $product->getPrice(),
                        'data-stock' => $product->getCurrentStockQuantity(),
                    ];
                },
                'label' => 'Product',
                'placeholder' => 'Select a product',
                'attr' => [
                    'class' => 'order-item-product w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bright-green focus:border-transparent transition-colors duration-200 text-dark-forest-green text-xs dark:bg-gray-700 dark:border-gray-500 dark:text-gray-300',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'class' => 'order-item-quantity w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bright-green focus:border-transparent transition-colors duration-200 text-dark-forest-green text-xs dark:bg-gray-700 dark:border-gray-500 dark:text-gray-300',
                ],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Unit Price',
                'scale' => 2,
                'html5' => true,
                'required' => false,
                'attr' => [
                    'readonly' => true,
                    'class' => 'order-item-unit-price w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-dark-forest-green text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300',
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $orderItem = $event->getData();
            $form = $event->getForm();
            if (!$orderItem instanceof OrderItem) {
                return;
            }

            $product = $orderItem->getProduct();
            $quantity = $orderItem->getQuantity() ?? 1;
            if ($product && $form->has('unitPrice')) {
                $form->get('unitPrice')->setData($product->getPrice());
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!is_array($data) || empty($data['product'])) {
                return;
            }

            $product = $this->productRepository->find($data['product']);
            if (!$product) {
                $form->addError(new FormError('The selected product is not available.'));
                return;
            }

            $quantity = max(1, (int) ($data['quantity'] ?? 1));
            $available = $product->getCurrentStockQuantity();
            if ($quantity > $available) {
                $form->addError(new FormError(sprintf('Only %d item(s) are available for this product.', $available)));
            }

            $data['unitPrice'] = $product->getPrice();
            $event->setData($data);
        });
    }

    private function calculateLineTotal(?string $price, int $quantity): string
    {
        if ($price === null) {
            return '0.00';
        }

        return bcmul($price, (string) $quantity, 2);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
