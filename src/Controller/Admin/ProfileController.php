<?php
namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use App\Service\ProfileImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\{Request, JsonResponse, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;



#[Route('/admin')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_admin_profile')]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('admin/profile_content.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/api/profile', name: 'app_admin_api_profile', methods: ['GET'])]
    public function apiProfile(\Symfony\Component\HttpFoundation\UrlHelper $urlHelper): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'profileImage' => $user->getProfileImage() ? $urlHelper->getAbsoluteUrl($user->getProfileImage()) : null,
            ]
        ]);
    }

    #[Route('/api/profile/update', name: 'app_admin_api_profile_update', methods: ['POST'])]
    public function apiProfileUpdate(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ActivityLogger $activityLogger,
        ProfileImageUploadService $profileImageUploadService,
        \Symfony\Component\HttpFoundation\UrlHelper $urlHelper,
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // if (!$this->isCsrfTokenValid('profile_update', $request->headers->get('X-CSRF-TOKEN'))) {
        //     return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        // }

        /** @var User $user */
        $user = $this->getUser();

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
                // Check if email is unique (except for current user)
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'This email is already in use'
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
                $hobbies = $request->request->all('hobbies'); // Symfony 6.3+ for array or use $request->get('hobbies', [])
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

            $uploadedFile = $request->files->get('profileImage');
            if ($uploadedFile instanceof UploadedFile && \UPLOAD_ERR_NO_FILE !== $uploadedFile->getError()) {
                $newProfilePath = $profileImageUploadService->replaceProfileImage($user, $uploadedFile);
                $changes['profileImage'] = [
                    'from' => $user->getProfileImage() ?: 'none',
                    'to' => $newProfilePath,
                ];
                $user->setProfileImage($newProfilePath);
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
                'profileImage' => $user->getProfileImage() ? $urlHelper->getAbsoluteUrl($user->getProfileImage()) : null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/profile/password', name: 'app_admin_api_profile_password', methods: ['POST'])]
    public function apiProfilePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // if (!$this->isCsrfTokenValid('profile_password', $request->headers->get('X-CSRF-TOKEN'))) {
        //     return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        // }
        /** @var User $user */
        $user = $this->getUser();
        $currentPassword = $request->request->getString('currentPassword');
        $newPassword = $request->request->getString('newPassword');

        try {
            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // Validate new password
            if (strlen($newPassword) < 8) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'New password must be at least 8 characters'
                ], 400);
            }

            // Update password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }


}