<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User\Consumer;
use App\Message\User\UserVerifyAccountMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<Consumer, Consumer>
 */
final readonly class ConsumerRegistrationProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $persistProcessor
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private MessageBusInterface $messageBus,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Consumer
    {
        if (!$data instanceof Consumer) {
            throw new \LogicException(sprintf('Expected %s, got %s.', Consumer::class, get_debug_type($data)));
        }

        $password = $data->getPassword();

        if (null === $password || '' === trim($password)) {
            throw new BadRequestHttpException('Le mot de passe est obligatoire et ne peut pas être vide.');
        }

        $data->setPassword($this->passwordHasher->hashPassword($data, $password));

        $confirmationToken = bin2hex(random_bytes(32));
        $data->setConfirmationToken($confirmationToken);

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        assert($result instanceof Consumer);

        $this->messageBus->dispatch(new UserVerifyAccountMessage(
            (string) $result->getEmail(),
            $confirmationToken
        ));

        return $result;
    }
}
