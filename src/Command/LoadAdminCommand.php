<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\DataFixtures\AdminFixture;

#[AsCommand(
    name: 'app:load-admin',
    description: 'Load admin accounts from fixture without affecting existing data',
    hidden: false
)]
class LoadAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdminFixture $adminFixture
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info('Loading admin fixture...');

            // Call the fixture's load method directly
            $this->adminFixture->load($this->entityManager);

            $io->success('Admin accounts loaded successfully!');
            $io->newLine();
            $io->text([
                'Admin Account Details:',
                '  Email: admin@growfico.com',
                '  Password: AdminGrowfico@2025',
                '  Role: ROLE_ADMIN',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to load admin fixture: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
