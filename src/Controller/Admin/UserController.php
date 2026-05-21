<?php
namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_users')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $status = $request->query->get('status');
        $users = $userRepository->findAll();

        if ($status === 'active') {
            $users = array_filter($users, fn($user) => $user->isActive());
        } elseif ($status === 'inactive') {
            $users = array_filter($users, fn($user) => !$user->isActive());
        }
        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'user' => $this->getUser(),
            'current_status' => $status,
        ]);
    }
  #[Route('/create', name: 'app_admin_users_create', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): JsonResponse
    {
        if (!$this->isCsrfTokenValid('admin_users', $request->headers->get('X-CSRF-TOKEN') ?? '')) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $user = new User();
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setEmail($data['email']);
            $user->setPhone($data['phone'] ?? null);
            $user->setIsVerified(true);
            $user->setIsActive($data['isActive'] ?? true);

            // Set role
            $roles = ['ROLE_USER'];
            if (($data['role'] ?? null) === 'ROLE_ADMIN') {
                $roles[] = 'ROLE_ADMIN';
            } elseif (($data['role'] ?? null) === 'ROLE_STAFF') {
                $roles[] = 'ROLE_STAFF';
            }
            $user->setRoles($roles);

            // Hash password
            if (!empty($data['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'CREATE_USER', sprintf('Created user %s', $user->getEmail()));

            return new JsonResponse([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/update', name: 'app_admin_users_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateUser(int $id, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): JsonResponse
    {
        if (!$this->isCsrfTokenValid('admin_users', $request->headers->get('X-CSRF-TOKEN') ?? '')) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            $data = json_decode($request->getContent(), true);

            // Track field changes
            $changes = [];

            if ($user->getFirstName() !== ($data['firstName'] ?? null)) {
                $changes['firstName'] = [
                    'from' => $user->getFirstName(),
                    'to' => $data['firstName']
                ];
                $user->setFirstName($data['firstName']);
            }

            if ($user->getLastName() !== ($data['lastName'] ?? null)) {
                $changes['lastName'] = [
                    'from' => $user->getLastName(),
                    'to' => $data['lastName']
                ];
                $user->setLastName($data['lastName']);
            }

            if ($user->getEmail() !== ($data['email'] ?? null)) {
                $changes['email'] = [
                    'from' => $user->getEmail(),
                    'to' => $data['email']
                ];
                $user->setEmail($data['email']);
            }

            if ($user->getPhone() !== ($data['phone'] ?? null)) {
                $changes['phone'] = [
                    'from' => $user->getPhone(),
                    'to' => $data['phone'] ?? null
                ];
                $user->setPhone($data['phone'] ?? null);
            }

            if ($user->isActive() !== ($data['isActive'] ?? true)) {
                $changes['isActive'] = [
                    'from' => $user->isActive() ? 'true' : 'false',
                    'to' => ($data['isActive'] ?? true) ? 'true' : 'false'
                ];
                $user->setIsActive($data['isActive'] ?? true);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());

            // Set role
            $roles = ['ROLE_USER'];
            if (($data['role'] ?? null) === 'ROLE_ADMIN') {
                $roles[] = 'ROLE_ADMIN';
            } elseif (($data['role'] ?? null) === 'ROLE_STAFF') {
                $roles[] = 'ROLE_STAFF';
            }

            $oldRoles = implode(',', $user->getRoles());
            $newRoles = implode(',', $roles);

            if ($oldRoles !== $newRoles) {
                $changes['roles'] = [
                    'from' => $oldRoles,
                    'to' => $newRoles
                ];
                $user->setRoles($roles);
            }

            // Update password if provided
            if (!empty($data['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
                $changes['password'] = [
                    'from' => '***',
                    'to' => '***'
                ];
            }

            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'UPDATE_USER', sprintf('Updated user %s', $user->getEmail()), !empty($changes) ? $changes : null);

            return new JsonResponse([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_users_toggle_status', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function toggleUserStatus(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, ActivityLogger $activityLogger, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        if (!$this->isCsrfTokenValid('admin_users', $request->headers->get('X-CSRF-TOKEN') ?? '')) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            $data = json_decode($request->getContent(), true);

            // Check if deactivating an admin account
            $isDeactivatingAdmin = !$data['isActive'] && in_array('ROLE_ADMIN', $user->getRoles());

            if ($isDeactivatingAdmin) {
                // Require password verification for admin deactivation
                if (empty($data['password'])) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Password is required to deactivate an admin account'
                    ], 403);
                }

                // Verify the current admin's password
                $currentUser = $this->getUser();
                if (!$passwordHasher->isPasswordValid($currentUser, $data['password'])) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Restricted. Admin deactivation not allowed.'
                    ], 403);
                }
            }

            $changes = [
                'isActive' => [
                    'from' => $user->isActive() ? 'true' : 'false',
                    'to' => $data['isActive'] ? 'true' : 'false'
                ]
            ];

            $user->setIsActive($data['isActive']);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'UPDATE_USER', sprintf('Toggled user %s active=%s', $user->getEmail(), $user->isActive() ? 'yes' : 'no'), $changes);

            return new JsonResponse([
                'success' => true,
                'message' => 'User status updated successfully',
                'isActive' => $user->isActive()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating user status: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/delete', name: 'app_admin_users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteUser(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): JsonResponse
    {
        if (!$this->isCsrfTokenValid('admin_users', $request->headers->get('X-CSRF-TOKEN') ?? '')) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            $entityManager->remove($user);
            $entityManager->flush();

            $activityLogger->log($this->getUser(), 'DELETE_USER', sprintf('Deleted user %s', $user->getEmail()));

            return new JsonResponse([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}', name: 'app_admin_users_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getUserData(int $id, UserRepository $userRepository, \Symfony\Component\HttpFoundation\UrlHelper $urlHelper): JsonResponse
    {
        try {
            $user = $userRepository->find($id);

            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'profileImage' => $user->getProfileImage() ? $urlHelper->getAbsoluteUrl($user->getProfileImage()) : null,
                    'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'lastLoginAt' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error fetching user: ' . $e->getMessage()
            ], 500);
        }
    }
}