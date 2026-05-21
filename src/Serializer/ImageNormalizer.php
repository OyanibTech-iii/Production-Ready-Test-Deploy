<?php

namespace App\Serializer;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Course;
use App\Entity\ChatMessage;
use App\Entity\UserQrCode;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Attribute\AsNormalizer;

#[AsNormalizer]
class ImageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'IMAGE_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private UrlHelper $urlHelper
    ) {
    }

    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        if (!is_array($data)) {
            return $data;
        }

        if ($object instanceof Product) {
            // Check all possible keys the frontend might use
            foreach (['image', 'productImage', 'product_image'] as $key) {
                if (isset($data[$key]) && $data[$key] && !str_starts_with($data[$key], 'http')) {
                    $image = ltrim($data[$key], '/');
                    $path = str_starts_with($image, 'uploads/images/') ? $image : 'uploads/images/' . $image;
                    $data[$key] = $this->urlHelper->getAbsoluteUrl($path);
                }
            }
        } elseif ($object instanceof User) {
            if (isset($data['profileImage']) && $data['profileImage'] && !str_starts_with($data['profileImage'], 'http')) {
                $data['profileImage'] = $this->urlHelper->getAbsoluteUrl(ltrim($data['profileImage'], '/'));
            }
        } elseif ($object instanceof Course) {
            if (isset($data['thumbnail']) && $data['thumbnail'] && !str_starts_with($data['thumbnail'], 'http')) {
                $thumbnail = ltrim($data['thumbnail'], '/');
                $path = str_starts_with($thumbnail, 'uploads/courses/') ? $thumbnail : 'uploads/courses/' . $thumbnail;
                $data['thumbnail'] = $this->urlHelper->getAbsoluteUrl($path);
            }
        } elseif ($object instanceof UserQrCode) {
            if (isset($data['qrCodePath']) && $data['qrCodePath'] && !str_starts_with($data['qrCodePath'], 'http')) {
                $data['qrCodePath'] = $this->urlHelper->getAbsoluteUrl(ltrim($data['qrCodePath'], '/'));
            }
        } elseif ($object instanceof ChatMessage) {
            if (isset($data['imagePath']) && $data['imagePath'] && !str_starts_with($data['imagePath'], 'http')) {
                $data['imagePath'] = $this->urlHelper->getAbsoluteUrl(ltrim($data['imagePath'], '/'));
            }
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Product || $data instanceof User || $data instanceof Course || $data instanceof UserQrCode || $data instanceof ChatMessage;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Product::class => false,
            User::class => false,
            Course::class => false,
            UserQrCode::class => false,
            ChatMessage::class => false,
        ];
    }
}
