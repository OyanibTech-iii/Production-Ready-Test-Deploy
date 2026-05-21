<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $accounts = [
            [
                'email' => 'useraccount@gmail.com',
                'roles' => [],
                'password' => 'useraccount@gmail.com',
                'firstName' => 'User',
                'lastName' => 'Account',
                'phone' => '09234567891',
                'profileImage' => '/uploads/profiles/profile_42_1775282857.png',
            ],
            [
                'email' => 'staffaccount@gmail.com',
                'roles' => ['ROLE_STAFF'],
                'password' => 'staffaccount@gmail.com',
                'firstName' => 'Staff',
                'lastName' => 'Account',
                'phone' => '0987654321',
                'profileImage' => '/uploads/profiles/profile_43_1775282924.png',
            ],
            [
                'email' => 'pacificooyanib@gmail.com',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'pacificooyanib@gmail.com',
                'firstName' => 'Admin',
                'lastName' => 'Account',
                'phone' => '0912345678',
                'profileImage' => '/uploads/profiles/profile_44_1775283002.png',
            ],
            [
                'email' => 'pacificooyanibdump@gmail.com',
                'roles' => [],
                'password' => 'pacificooyanibdump@gmail.com',
                'firstName' => 'Growfica',
                'lastName' => 'Store',
                'phone' => '',
                'profileImage' => '/uploads/profiles/profile_45_1775288094.png',
            ],
            [
                'email' => 'growficoofficial@gmail.com',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'growficoofficial@gmail.com',
                'firstName' => 'Growfico official',
                'lastName' => 'Administrator',
                'phone' => '',
                'profileImage' => '/uploads/profiles/profile_46_1775288940.png',
            ],
        ];

        $userRepo = $manager->getRepository(User::class);

        foreach ($accounts as $data) {
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
            $user->setIsVerified(true);

            $hashed = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashed);

            $manager->persist($user);
        }

        $manager->flush();
    }

}

