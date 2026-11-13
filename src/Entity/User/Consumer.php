<?php

namespace App\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\User\Address\Address;
use App\Repository\User\ConsumerRepository;
use App\Security\Voter\ConsumerVoter;
use App\State\User\ConsumerPasswordChangeProcessor;
use App\State\User\ConsumerRegistrationProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[ORM\OneToOne(
        mappedBy: 'consumer',
        targetEntity: Identity::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[Assert\NotNull(message: 'form.identity.required')]
    #[Assert\Valid]
    private ?Identity $identity = null;

    #[ORM\OneToOne(
        mappedBy: 'consumer',
        targetEntity: Address::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[Assert\NotNull(message: 'form.address.required')]
    #[Assert\Valid]
    private ?Address $address = null;

    #[Groups(['consumer:read'])]
    public function getRoles(): array
    {
        return array_unique(array_merge(parent::getRoles(), ['ROLE_CONSUMER']));
    }

    public function getIdentity(): ?Identity
    {
        return $this->identity;
    }

    public function setIdentity(Identity $identity): self
    {
        if ($this->identity === $identity) {
            return $this;
        }

        $this->identity = $identity;

        if ($identity->getConsumer() !== $this) {
            $identity->setConsumer($this);
        }

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): self
    {
        if ($this->address === $address) {
            return $this;
        }

        $this->address = $address;

        if ($address->getConsumer() !== $this) {
            $address->setConsumer($this);
        }

        return $this;
    }
}
