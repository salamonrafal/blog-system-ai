<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create-admin', description: 'Create an administrator account.')]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password')
            ->addOption('inactive', null, InputOption::VALUE_NONE, 'Create user without active access');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (!is_string($email) || '' === trim($email)) {
            $email = $io->ask('Admin email');
        }

        $password = $input->getArgument('password');
        if (!is_string($password) || '' === trim($password)) {
            $password = $io->askHidden('Admin password');
        }

        if (!is_string($email) || '' === trim($email)) {
            $io->error('Email is required.');

            return Command::FAILURE;
        }

        if (!is_string($password) || '' === trim($password)) {
            $io->error('Password is required.');

            return Command::FAILURE;
        }

        if ($this->userRepository->findOneByEmail($email)) {
            $io->error('A user with this email already exists.');

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsActive(!$input->getOption('inactive'));
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Administrator %s has been created.', $user->getEmail()));

        return Command::SUCCESS;
    }
}
