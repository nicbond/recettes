<?php

namespace App\Serializer;

use App\Entity\Recipe\Recipe;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

final class RecipePublicNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = self::class.'_already_called';

    public function __construct(private readonly StorageInterface $storage, private readonly UrlHelper $urlHelper)
    {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        $groups = $context['groups'] ?? [];

        return $data instanceof Recipe
            && is_array($groups)
            && in_array('recipe:public:read', $groups, true)
            && !isset($context[self::ALREADY_CALLED]);
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Recipe::class => false];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>|string|int|float|bool|\ArrayObject<array-key, mixed>|null
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!$data instanceof Recipe) {
            return $this->normalizer->normalize($data, $format, $context);
        }

        $context[self::ALREADY_CALLED] = true;

        $normalizedData = $this->normalizer->normalize($data, $format, $context);

        if (!is_array($normalizedData)) {
            return $normalizedData;
        }

        $uri = $this->storage->resolveUri($data, 'thumbnailFile');

        $normalizedData['thumbnailUrl'] = null !== $uri
            ? $this->urlHelper->getAbsoluteUrl($uri)
            : null;

        return $normalizedData;
    }
}
