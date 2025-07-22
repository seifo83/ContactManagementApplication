<?php

namespace App\Entity;

use App\Entity\Trait\SoftDeletable;
use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    use SoftDeletable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(name: 'street', type: 'string', length: 255, nullable: true)]
    public ?string $street = null;

    #[ORM\Column(name: 'street2', type: 'string', length: 255, nullable: true)]
    public ?string $street2 = null;

    #[ORM\Column(name: 'manual_zip_code', type: 'string', nullable: true)]
    public ?string $manualZipCode = null;

    #[ORM\Column(name: 'manual_city', type: 'string', nullable: true)]
    public ?string $manualCity = null;

    #[ORM\Column(name: 'city', type: 'string', nullable: true)]
    public ?string $city = null;

    #[ORM\Column(name: 'zip_code', type: 'string', nullable: true)]
    public ?string $zipCode = null;

    #[ORM\Column(name: 'country', type: 'string', nullable: true)]
    public ?string $country = null;

    public function getCityName(): ?string
    {
        return $this->city ?? $this->manualCity;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode ?? $this->manualZipCode;
    }
}
