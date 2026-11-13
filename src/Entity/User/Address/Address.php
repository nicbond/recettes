<?php

namespace App\Entity\User\Address;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\User\Consumer;
use App\Repository\User\Address\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[SerializedName('id')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 50)]
    #[SerializedName('street')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => '7 rue de la Paix',
        ],
    )]
    private string $street;

    #[ORM\Column(length: 50, nullable: true)]
    #[SerializedName('complement')]
    #[Assert\Length(max: 50)]
    #[Groups(['consumer:read', 'consumer:write'])]
    private ?string $complement = null;

    #[ORM\Column(length: 5)]
    #[SerializedName('postalCode')]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{5}$/',
        message: 'form.postal_code.invalid',
    )]
    #[Assert\Length(max: 5)]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => '75002',
        ],
    )]
    private string $postalCode;

    #[ORM\Column(length: 40)]
    #[SerializedName('city')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 40)]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => 'Paris',
        ],
    )]
    private string $city;

    #[ORM\Column(length: 2)]
    #[SerializedName('countryCode')]
    #[Assert\NotBlank]
    #[Assert\Country]
    #[Groups(['consumer:read', 'consumer:write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => 'FR',
        ],
    )]
    private string $countryCode = 'FR';

    #[ORM\Column(length: 10, nullable: true)]
    #[SerializedName('phoneNumber')]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(
        pattern: '/^0[1-9](?:\d{2}){4}$/',
        message: 'form.phone_number.invalid',
    )]
    #[Groups(['consumer:read', 'consumer:write'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[SerializedName('extraInformation')]
    #[Groups(['consumer:read', 'consumer:write'])]
    private ?string $extraInformation = null;

    #[ORM\OneToOne(
        targetEntity: Consumer::class,
        inversedBy: 'address',
    )]
    #[ORM\JoinColumn(
        name: 'consumer_id',
        referencedColumnName: 'id',
        nullable: false,
        unique: true,
    )]
    private ?Consumer $consumer = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getComplement(): ?string
    {
        return $this->complement;
    }

    public function setComplement(?string $complement): self
    {
        $this->complement = $complement;

        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getExtraInformation(): ?string
    {
        return $this->extraInformation;
    }

    public function setExtraInformation(?string $extraInformation): self
    {
        $this->extraInformation = $extraInformation;

        return $this;
    }

    public function getConsumer(): ?Consumer
    {
        return $this->consumer;
    }

    public function setConsumer(Consumer $consumer): self
    {
        $this->consumer = $consumer;

        if ($consumer->getAddress() !== $this) {
            $consumer->setAddress($this);
        }

        return $this;
    }
}
