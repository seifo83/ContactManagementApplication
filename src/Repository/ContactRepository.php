<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository implements ContactRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findNotUpdatedSince(\DateTimeImmutable $threshold): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.updatedAt < :threshold')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
