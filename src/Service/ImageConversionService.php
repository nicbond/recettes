<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

final readonly class ImageConversionService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Converts a WebP image to a base64-encoded JPEG string for use in PDF generation.
     * Returns null if the image cannot be converted.
     */
    public function convertToBase64(string $imagePath): ?string
    {
        if (!file_exists($imagePath)) {
            $this->logger->error('Image not found', ['path' => $imagePath]);

            return null;
        }

        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $imageData = file_get_contents($imagePath);

        if (false === $imageData) {
            $this->logger->error('Failed to read image file', ['path' => $imagePath]);

            return null;
        }

        if ('webp' === $extension && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($imagePath);

            if (false === $image) {
                $this->logger->error('Failed to convert WebP image', ['path' => $imagePath]);

                return null;
            }

            ob_start();
            imagejpeg($image, null, 90);
            $imageData = ob_get_clean();
            imagedestroy($image);

            if (false === $imageData) {
                return null;
            }

            $extension = 'jpeg';
        }

        if ('jpg' === $extension) {
            $extension = 'jpeg';
        }

        $base64Data = str_replace(["\r", "\n"], '', base64_encode($imageData));

        return 'data:image/'.$extension.';base64,'.$base64Data;
    }
}
