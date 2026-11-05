<?php

namespace App\MessageHandler\User;

use App\Entity\User\Consumer;
use App\Entity\User\User;
use App\Event\UserVerifyRequestEvent;
use App\Message\User\UserVerifyAccountMessage;
use App\Repository\User\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[AsMessageHandler]
final readonly class UserVerifyAccountMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private EventDispatcherInterface $dispatcher,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        #[Autowire('%app.front_url%')]
        private string $frontUrl,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(UserVerifyAccountMessage $message): void
    {
        $user = $this->userRepository->findOneByConfirmationToken($message->token);

        if (!$user instanceof User) {
            $this->logger->error('UserVerifyAccountMessage : Unable to verify user', [
                'email' => $message->email,
            ]);

            throw new \Exception(sprintf('Unable to verify user %s', $message->email));
        }

        $routeName = $user instanceof Consumer
            ? 'app_activate_consumer_account'
            : 'app_activate_account';

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            (string) $user->getId(),
            (string) $user->getEmail(),
            [
                'id' => $user->getId(),
                'token' => $message->token,
            ]
        );

        $signedUrl = $signatureComponents->getSignedUrl();

        // Context: Consumers must interact exclusively with the Front-End.
        // Extracting signature parameters to route the verification link to the Front-End app,
        // which will then call back our API to perform the database update.
        if ($user instanceof Consumer) {
            $queryString = parse_url($signedUrl, PHP_URL_QUERY);

            if (!is_string($queryString)) {
                throw new \LogicException('Unable to generate Consumer verification URL.');
            }

            $signedUrl = rtrim($this->frontUrl, '/')
                .'/verification-compte?'
                .$queryString;
        }

        $event = new UserVerifyRequestEvent($user);
        $event->setSignatureUrl($signedUrl);

        $this->dispatcher->dispatch($event);

        $this->logger->info(
            'UserVerifyAccountMessage : UserVerifyAccount email sent successfully',
            [
                'email' => $user->getEmail(),
            ]
        );
    }
}
