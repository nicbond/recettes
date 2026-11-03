<?php

namespace App\Entity\User;

use ApiPlatform\Metadata\ApiProperty;
use App\Enum\Gender;
use App\Repository\User\IdentityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IdentityRepository::class)]
class Identity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[SerializedName('id')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 5, nullable: true, enumType: Gender::class)]
    #[SerializedName('gender')]
    #[Groups(['consumer:read', 'consumer:write'])]
    private ?Gender $gender = null;

    #[ORM\Column(length: 25)]
    #[SerializedName('firstname')]
    #[Assert\Length(max: 25)]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z-ëêéèâàäôïîç ]*$/',
        message: 'Numbers or special characters are not allowed'
    )]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'format' => 'string',
            'example' => 'James',
        ],
    )]
    private string $firstName;

    #[ORM\Column(length: 25)]
    #[SerializedName('lastname')]
    #[Assert\Length(max: 25)]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z-ëêéèâàäôïîç ]*$/',
        message: 'Numbers or special characters are not allowed'
    )]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'format' => 'string',
            'example' => 'Bond',
        ],
    )]
    private string $lastName;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[SerializedName('birthdate')]
    #[Assert\GreaterThan('1900-01-01')]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'format' => 'date',
            'example' => '1990-05-12',
        ],
    )]
    private ?\DateTimeImmutable $birthdate = null;

    #[ORM\OneToOne(
        targetEntity: Consumer::class,
        inversedBy: 'identity'
    )]
    #[ORM\JoinColumn(
        name: 'consumer_id',
        referencedColumnName: 'id',
        nullable: false,
        unique: true
    )]
    private ?Consumer $consumer = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFormattedGender(): ?string
    {
        return $this->gender?->label();
    }

    public function getBirthdate(): ?\DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeImmutable $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getConsumer(): ?Consumer
    {
        return $this->consumer;
    }

    public function setConsumer(Consumer $consumer): self
    {
        $this->consumer = $consumer;

        if ($consumer->getIdentity() !== $this) {
            $consumer->setIdentity($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->firstName;
    }
}
