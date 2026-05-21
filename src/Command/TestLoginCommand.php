<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-login',
    description: 'Test login functionality and user roles',
)]
class TestLoginCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        $io->title('User Login Test Results');
        
        foreach ($users as $user) {
            $io->section('User: ' . $user->getEmail());
            $io->table(
                ['Property', 'Value'],
                [
                    ['ID', $user->getId()],
                    ['Email', $user->getEmail()],
                    ['Roles', implode(', ', $user->getRoles())],
                    ['Is Verified', $user->isVerified() ? 'Yes' : 'No'],
                    ['Full Name', $user->getFullname()],
                ]
            );
            
            // Determine expected redirect
            $roles = array_map('strtoupper', $user->getRoles());
            if (in_array('ROLE_ADMIN', $roles, true)) {
                $expectedRedirect = '/admin/';
            } elseif (in_array('ROLE_MODERATOR', $roles, true)) {
                $expectedRedirect = '/dashboard/';
            } else {
                $expectedRedirect = '/userpage/';
            }
            
            $io->text('Expected redirect after login: ' . $expectedRedirect);
            $io->newLine();
        }

        return Command::SUCCESS;
    }
}
