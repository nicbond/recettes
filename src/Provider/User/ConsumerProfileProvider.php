<?php

namespace App\Provider\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User\Consumer;
use App\Repository\User\ConsumerRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProviderInterface<Consumer>
 */
final class ConsumerProfileProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ConsumerRepository $consumerRepository,
    ) {
    }

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Consumer {
        $user = $this->security->getUser();

        if (!$user instanceof Consumer || null === $user->getId()) {
            throw new AccessDeniedException();
        }

        $consumer = $this->consumerRepository->find($user->getId());

        if (!$consumer instanceof Consumer) {
            throw new AccessDeniedException();
        }

        return $consumer;
    }
}
