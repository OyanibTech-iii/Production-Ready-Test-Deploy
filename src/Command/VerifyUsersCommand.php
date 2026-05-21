<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:verify-users',
    description: 'Verify user accounts (set is_verified to true)',
)]
class VerifyUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Verify specific user by email')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Verify all users')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getOption('email');
        $all = $input->getOption('all');

        if ($email) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $io->error(sprintf('User with email "%s" not found.', $email));
                return Command::FAILURE;
            }
            
            $user->setIsVerified(true);
            $this->entityManager->flush();
            
            $io->success(sprintf('User "%s" has been verified.', $email));
        } elseif ($all) {
            $users = $this->entityManager->getRepository(User::class)->findAll();
            $count = 0;
            
            foreach ($users as $user) {
                if (!$user->isVerified()) {
                    $user->setIsVerified(true);
                    $count++;
                }
            }
            
            $this->entityManager->flush();
            
            $io->success(sprintf('Verified %d users.', $count));
        } else {
            $io->error('Please specify either --email or --all option.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
