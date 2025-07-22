<?php

namespace App\Repository;

use App\Entity\Contact;

interface ContactRepositoryInterface
{
    /**
     * @return Contact[]
     */
    public function findNotUpdatedSince(\DateTimeImmutable $threshold): array;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;
}
