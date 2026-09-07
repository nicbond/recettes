<?php

namespace App\EventListener\Restrictions;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class MaintenanceListener
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @throws \Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (empty($_ENV['ACTIVE_MAINTENANCE_PAGE'])) {
            return;
        }

        $request = $event->getRequest();
        if ('/maintenance' === $request->getPathInfo()) {
            return;
        }

        $clientIp = $request->getClientIp();
        $allowedIp = [];

        if (isset($_ENV['ALLOWED_IP']) && is_string($_ENV['ALLOWED_IP']) && '' !== $_ENV['ALLOWED_IP']) {
            $allowedIp = array_map('trim', explode(',', $_ENV['ALLOWED_IP']));
        }

        if (in_array($clientIp, $allowedIp, true)) {
            return;
        }

        $maintenanceUrl = $this->urlGenerator->generate('maintenance');
        $response = new RedirectResponse($maintenanceUrl, Response::HTTP_FOUND);

        $event->setResponse($response);
    }
}
