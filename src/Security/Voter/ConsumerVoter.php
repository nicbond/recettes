<?php

namespace App\Security\Voter;

use App\Entity\User\Consumer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Consumer>
 */
final class ConsumerVoter extends Voter
{
    public const VIEW = 'CONSUMER_VIEW';
    public const EDIT = 'CONSUMER_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Consumer;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Consumer) {
            return false;
        }

        /** @var Consumer $subject */
        if ($user->getId() !== $subject->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT => true,
            default => false,
        };
    }
}
