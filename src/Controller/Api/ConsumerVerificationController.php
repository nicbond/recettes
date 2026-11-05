<?php

namespace App\Controller\Api;

use App\Entity\User\Consumer;
use App\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class ConsumerVerificationController extends AbstractController
{
    #[Route(
        '/api/consumers/activate',
        name: 'app_activate_consumer_account',
        methods: ['POST']
    )]
    public function activate(
        #[MapQueryParameter] Uuid $id,
        Request $request,
        UserRepository $userRepository,
        VerifyEmailHelperInterface $verifyEmailHelper,
        TranslatorInterface $translator,
    ): JsonResponse {

        $user = $userRepository->findOneByIdAndConfirmationTokenNotNull($id);

        if (!$user instanceof Consumer) {
            $message = $translator->trans('consumer.verification.not_found');

            return $this->json(['message' => $message], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string) $user->getId(),
                (string) $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface) {
            $message = $translator->trans('consumer.verification.invalid_link');

            return $this->json(['message' => $message], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);

        $userRepository->flush();

        $message = $translator->trans('consumer.verification.success');

        return $this->json(['message' => $message]);
    }
}
