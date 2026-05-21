<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use App\Service\ProfileImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/me', name: 'app_user_profile_me', methods: ['GET'])]
    public function me(\Symfony\Component\HttpFoundation\UrlHelper $urlHelper): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'success' => true,
            'user' => [
                'id' => $user?->getId(),
                'firstName' => $user?->getFirstName(),
                'lastName' => $user?->getLastName(),
                'email' => $user?->getEmail(),
                'phone' => $user?->getPhone(),
                'profileImage' => $user?->getProfileImage() ? $urlHelper->getAbsoluteUrl($user->getProfileImage()) : null,
                'roles' => $user?->getRoles(),
            ],
        ]);
    }

    #[Route('/update', name: 'app_user_profile_update', methods: ['POST'])]
    public function update(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ActivityLogger $activityLogger,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $changes = [];

        try {
            // Update basic information
            if ($request->request->has('firstName')) {
                $newValue = $request->request->getString('firstName');
                if ($user->getFirstName() !== $newValue) {
                    $changes['firstName'] = [
                        'from' => $user->getFirstName(),
                        'to' => $newValue
                    ];
                }
                $user->setFirstName($newValue);
            }

            if ($request->request->has('lastName')) {
                $newValue = $request->request->getString('lastName');
                if ($user->getLastName() !== $newValue) {
                    $changes['lastName'] = [
                        'from' => $user->getLastName(),
                        'to' => $newValue
                    ];
                }
                $user->setLastName($newValue);
            }

            if ($request->request->has('email')) {
                $email = $request->request->getString('email');
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'This email is already in use',
                    ], 400);
                }

                if ($user->getEmail() !== $email) {
                    $changes['email'] = [
                        'from' => $user->getEmail(),
                        'to' => $email
                    ];
                }
                $user->setEmail($email);
            }

            if ($request->request->has('phone')) {
                $newValue = $request->request->getString('phone');
                if ($user->getPhone() !== $newValue) {
                    $changes['phone'] = [
                        'from' => $user->getPhone(),
                        'to' => $newValue
                    ];
                }
                $user->setPhone($newValue);
            }

            // --- UserProfile Logic ---
            $profile = $user->getUserProfile();
            if (!$profile) {
                $profile = new \App\Entity\UserProfile();
                $profile->setUser($user);
                $entityManager->persist($profile);
                $changes['profile'] = 'Created new user profile';
            }

            if ($request->request->has('bio')) {
                $bio = substr($request->request->getString('bio'), 0, 101);
                if ($profile->getBio() !== $bio) {
                    $changes['bio'] = ['from' => $profile->getBio(), 'to' => $bio];
                }
                $profile->setBio($bio);
            }

            if ($request->request->has('location')) {
                $location = $request->request->getString('location');
                if ($profile->getLocation() !== $location) {
                    $changes['location'] = ['from' => $profile->getLocation(), 'to' => $location];
                }
                $profile->setLocation($location);
            }

            if ($request->request->has('hobbies')) {
                $hobbies = $request->request->all('hobbies');
                if ($profile->getHobbies() !== $hobbies) {
                    $changes['hobbies'] = ['from' => $profile->getHobbies(), 'to' => $hobbies];
                }
                $profile->setHobbies($hobbies);
            }

            if ($request->request->has('facebookLink')) {
                $profile->setFacebookLink($request->request->getString('facebookLink'));
            }
            if ($request->request->has('instagramLink')) {
                $profile->setInstagramLink($request->request->getString('instagramLink'));
            }
            if ($request->request->has('twitterLink')) {
                $profile->setTwitterLink($request->request->getString('twitterLink'));
            }
            if ($request->request->has('linkedinLink')) {
                $profile->setLinkedinLink($request->request->getString('linkedinLink'));
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Log the activity
            $logTarget = 'Updated own profile';
            if (!empty($changes)) {
                $activityLogger->log($user, 'UPDATE_USER', $logTarget, $changes);
            } else {
                $activityLogger->log($user, 'UPDATE_USER', $logTarget);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/upload-image', name: 'app_user_profile_upload_image', methods: ['POST'])]
    public function uploadImage(
        Request $request,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger,
        ProfileImageUploadService $profileImageUploadService,
        \Symfony\Component\HttpFoundation\UrlHelper $urlHelper,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        try {
            $uploadedFile = $request->files->get('profileImage');
            if (!$uploadedFile instanceof UploadedFile || \UPLOAD_ERR_NO_FILE === $uploadedFile->getError()) {
                return new JsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
            }

            $newProfilePath = $profileImageUploadService->replaceProfileImage($user, $uploadedFile);
            
            $changes = [
                'profileImage' => [
                    'from' => $user->getProfileImage() ?: 'none',
                    'to' => $newProfilePath,
                ],
            ];
            
            $user->setProfileImage($newProfilePath);
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            // Log the activity
            $activityLogger->log($user, 'UPDATE_USER', 'Updated own profile image', $changes);

            return new JsonResponse([
                'success' => true,
                'message' => 'Profile image updated successfully',
                'profileImage' => $urlHelper->getAbsoluteUrl($user->getProfileImage()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/password', name: 'app_user_profile_password', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        // Block direct password change for Google users
        if ($user->getProvider() === 'google') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Direct password changes are not allowed for accounts linked with Google. Please request a change from the admin.',
            ], 403);
        }

        $currentPassword = $request->request->getString('currentPassword');
        $newPassword = $request->request->getString('newPassword');

        try {
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 400);
            }

            if (strlen($newPassword) < 8) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'New password must be at least 8 characters',
                ], 400);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Log the activity
            $changes = [
                'password' => [
                    'from' => '***',
                    'to' => '***'
                ]
            ];
            $activityLogger->log($user, 'UPDATE_USER', sprintf('Changed password: %s', $user->getEmail()), $changes);

            return new JsonResponse([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/request-password-change', name: 'app_user_profile_request_password', methods: ['POST'])]
    public function requestPasswordChange(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        \App\Service\NotificationService $notificationService,
        ActivityLogger $activityLogger
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($user->getProvider() !== 'google') {
            return new JsonResponse(['success' => false, 'message' => 'This feature is only for Google-linked accounts.'], 400);
        }

        try {
            // Find admins to notify
            $admins = $userRepository->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%"ROLE_ADMIN"%')
                ->getQuery()
                ->getResult();

            $message = sprintf('User %s (%s) has requested a password change for their Google-linked account.', $user->getFullName(), $user->getEmail());

            foreach ($admins as $admin) {
                $notificationService->create(
                    $admin,
                    'Password Change Request',
                    $message,
                    'warning',
                    'high'
                );
            }

            $activityLogger->log($user, 'UPDATE_USER', 'Requested password change from admin');

            return new JsonResponse([
                'success' => true,
                'message' => 'Your request has been sent to the administrator. You will be notified once it is processed.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}


