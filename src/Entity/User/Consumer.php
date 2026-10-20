<?php

namespace App\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\User\ConsumerRepository;
use App\Security\Voter\ConsumerVoter;
use App\State\User\ConsumerPasswordChangeProcessor;
use App\State\User\ConsumerRegistrationProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ConsumerRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            security: "is_granted('PUBLIC_ACCESS')",
            processor: ConsumerRegistrationProcessor::class,
            normalizationContext: ['groups' => ['consumer:read']],
            denormalizationContext: ['groups' => ['consumer:write', 'consumer:password:write']],
        ),
        new Get(
            security: "is_granted('".ConsumerVoter::VIEW."', object)",
            normalizationContext: ['groups' => ['consumer:read']],
        ),
        new Patch(
            security: "is_granted('".ConsumerVoter::EDIT."', object)",
            normalizationContext: ['groups' => ['consumer:read']],
            denormalizationContext: ['groups' => ['consumer:write']],
        ),
        new Patch(
            uriTemplate: '/consumers/{id}/password',
            security: "is_granted('".ConsumerVoter::EDIT."', object)",
            normalizationContext: ['groups' => ['consumer:read']],
            denormalizationContext: ['groups' => ['consumer:password:write']],
            processor: ConsumerPasswordChangeProcessor::class,
        ),
    ],
)]

class Consumer extends User
{
    #[Groups(['consumer:read'])]
    public function getRoles(): array
    {
        return array_unique(array_merge(parent::getRoles(), ['ROLE_CONSUMER']));
    }
}
