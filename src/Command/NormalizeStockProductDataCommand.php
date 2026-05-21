<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:normalize-stock-product-data',
    description: 'Normalize data between Stock and Product entities',
)]
class NormalizeStockProductDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Normalizing Stock and Product Data');
        
        try {
            $products = $this->entityManager->getRepository(Product::class)->findAll();
            $stocks = $this->entityManager->getRepository(Stock::class)->findAll();
            
            $io->info(sprintf('Found %d products and %d stock entries', count($products), count($stocks)));
            
            $createdStocks = 0;
            $linkedProducts = 0;
            
            foreach ($products as $product) {
                if ($product->getStock() === null) {
                    $stock = new Stock();
                    $stock->setQuantity(0);
                    $stock->setStockType('inventory');
                    $stock->setMinimumQuantity(0);
                    $stock->setCreatedAt(new \DateTime());
                    $stock->setUpdatedAt(new \DateTime());
                    
                    $this->entityManager->persist($stock);
                    $createdStocks++;
                    
                    $product->setStock($stock);
                    $linkedProducts++;
                }
            }
            
            foreach ($stocks as $stock) {
                if ($stock->getCreatedAt() === null) {
                    $stock->setCreatedAt(new \DateTime());
                }
                $stock->setUpdatedAt(new \DateTime());
                
                if ($stock->getStockType() === null) {
                    $stock->setStockType('inventory');
                }
                
                if ($stock->getMinimumQuantity() === null) {
                    $stock->setMinimumQuantity(0);
                }
            }
            
            $this->entityManager->flush();
            
            $io->success([
                sprintf('Created %d new stock entries', $createdStocks),
                sprintf('Linked %d products to stock entries', $linkedProducts),
                'Data normalization completed successfully!'
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Error during normalization: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}