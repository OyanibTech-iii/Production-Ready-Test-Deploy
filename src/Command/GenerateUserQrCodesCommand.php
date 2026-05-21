<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\QrCodeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'app:generate-user-qrcodes',
    description: 'Generates QR codes for all users who don\'t have one',
)]
class GenerateUserQrCodesCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private QrCodeService $qrCodeService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force regeneration of all QR codes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $users = $this->userRepository->findAll();
        $count = 0;

        foreach ($users as $user) {
            if ($force || !$user->getQrCode()) {
                $this->qrCodeService->generateForUser($user);
                $count++;
            }
        }

        $io->success(sprintf('Processed %d QR codes.', $count));

        return Command::SUCCESS;
    }
}
