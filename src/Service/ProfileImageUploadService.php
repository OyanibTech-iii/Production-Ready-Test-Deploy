<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProfileImageUploadService
{
    private const VALID_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * Stores the upload under public/uploads/profiles, archives any previous image, returns web path (e.g. /uploads/profiles/…).
     *
     * @throws \InvalidArgumentException For disallowed type or invalid upload
     */
    public function replaceProfileImage(User $user, UploadedFile $uploadedFile): string
    {
        if (!$uploadedFile->isValid() || $uploadedFile->getError() !== \UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('File upload failed or no file was provided.');
        }

        $ext = strtolower($uploadedFile->getClientOriginalExtension());
        if (!\in_array($ext, self::VALID_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                'Invalid file type. Only ' . implode(', ', self::VALID_EXTENSIONS) . ' are allowed.',
            );
        }

        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $uploadDir = $projectDir . '/public/uploads/profiles';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $oldRelative = $user->getProfileImage();
        if ($oldRelative) {
            $oldPath = $projectDir . '/public' . $oldRelative;
            if (is_file($oldPath)) {
                $archiveDir = $uploadDir . '/archive';
                if (!is_dir($archiveDir)) {
                    mkdir($archiveDir, 0755, true);
                }
                $archiveBase = basename($oldPath, '.' . pathinfo($oldPath, PATHINFO_EXTENSION));
                $archiveExt = pathinfo($oldPath, PATHINFO_EXTENSION);
                $archiveFileName = $archiveBase . '_' . time() . '.' . $archiveExt;
                rename($oldPath, $archiveDir . '/' . $archiveFileName);
            }
        }

        $fileName = 'profile_' . $user->getId() . '_' . time() . '.' . $ext;
        $uploadedFile->move($uploadDir, $fileName);

        return '/uploads/profiles/' . $fileName;
    }
}
