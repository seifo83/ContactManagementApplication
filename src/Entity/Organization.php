<?php

namespace App\Entity;

use App\Entity\Trait\Hashable;
use App\Entity\Trait\SoftDeletable;
use App\Entity\Trait\Timestampable;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
class Organization implements HashableInterface
{
    use Hashable;
    use Timestampable;
    use SoftDeletable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(name: 'technical_id', type: 'string', length: 60, nullable: true)]
    public ?string $technicalId = null;

    #[ORM\Column(name: 'name', type: 'string', nullable: true)]
    public ?string $name = null;

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'address_id', referencedColumnName: 'id')]
    #[Assert\Valid]
    public ?Address $address = null;

    #[ORM\Column(name: 'email_address', type: 'string', nullable: true)]
    #[Assert\Email(mode: 'strict')]
    public ?string $emailAddress = null;

    #[ORM\Column(name: 'phone_number', type: 'string', length: 15, nullable: true)]
    #[Assert\Regex(pattern: "/^$|^\w{9,15}$/")]
    public ?string $phoneNumber = null;

    #[ORM\Column(name: 'private', type: 'boolean', options: ['default' => 0])]
    public bool $private = false;
}
