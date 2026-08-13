<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;

class ImageConvertSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_UPLOAD => 'onPreUpload',
        ];
    }

    public function onPreUpload(Event $event): void
    {
        $file = $event->getMapping()->getFile($event->getObject());

        if (!$file instanceof UploadedFile) {
            return;
        }

        $mimeType = $file->getMimeType();

        if ('image/webp' === $mimeType) {
            return;
        }

        $path = $file->getRealPath();

        $image = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            default => null,
        };

        if (null === $image) {
            return;
        }

        imagewebp($image, $path, 85);
        imagedestroy($image);
    }
}
