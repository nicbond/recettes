<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User\Consumer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<Consumer, Consumer>
 */
final readonly class ConsumerPasswordChangeProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $persistProcessor
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Consumer
    {
        if (!$data instanceof Consumer) {
            $result = $this->persistProcessor->process(
                $data,
                $operation,
                $uriVariables,
                $context,
            );

            assert($result instanceof Consumer);

            return $result;
        }

        $previousData = $context['previous_data'] ?? null;
        $password = $data->getPassword();

        if (null === $password || '' === trim($password)) {
            throw new BadRequestHttpException('Le nouveau mot de passe est obligatoire et ne peut pas être vide.');
        }

        if ($previousData instanceof Consumer && $password === $previousData->getPassword()) {
            throw new BadRequestHttpException('Veuillez fournir un nouveau mot de passe.');
        }

        $data->setPassword($this->passwordHasher->hashPassword($data, $password));

        $result = $this->persistProcessor->process(
            $data,
            $operation,
            $uriVariables,
            $context,
        );

        assert($result instanceof Consumer);

        return $result;
    }
}
