<?php

namespace App\Controller\Api;

use App\Entity\User\Consumer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('ROLE_CONSUMER')]
    public function __invoke(): JsonResponse
    {
        $consumer = $this->getUser();

        if (!$consumer instanceof Consumer) {
            throw $this->createAccessDeniedException();
        }

        return $this->json(
            $consumer,
            context: ['groups' => ['consumer:read']]
        );
    }
}
