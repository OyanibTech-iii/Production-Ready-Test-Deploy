<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admins = [
            [
                'email' => 'growficoadmin@gmail.com',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'growficoadmin@gmail.com',
                'firstName' => 'Administrator ',
                'lastName' => 'Account',
                'phone' => '09123456789',
                'profileImage' => '/uploads/profiles/profile_41_1775282800.png',
            ],
        ];

        $userRepo = $manager->getRepository(User::class);

        foreach ($admins as $data) {
            // Skip if user already exists to avoid duplicates
            if ($userRepo->findOneBy(['email' => $data['email']])) {
                continue;
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setPhone($data['phone']);
            $user->setRoles($data['roles']);
            $user->setProfileImage($data['profileImage']);
            $user->setIsActive(true);
            $user->setIsVerified(true);

            $hashed = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashed);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
