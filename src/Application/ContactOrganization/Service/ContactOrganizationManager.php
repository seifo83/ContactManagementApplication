<?php

namespace App\Application\ContactOrganization\Service;

use App\Application\Common\Doctrine\DoctrineResetTrait;
use App\Repository\ContactRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class ContactOrganizationManager
{
    use DoctrineResetTrait;

    private int $batchSize = 100;

    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly OrganizationRepository $organizationRepository,
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry,
        LoggerInterface $logger,
    ) {
        $this->entityManager = $entityManager;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    public function createOrUpdate(string $ppIdentifier, string $organizationIdentifier, int $lineNumber): void
    {
        $contact = $this->contactRepository->findOneBy(['ppIdentifier' => $ppIdentifier]);
        if (!$contact) {
            $this->logger->warning(sprintf(
                'Ligne %d : Contact non trouvé pour Identifiant PP %s',
                $lineNumber,
                $ppIdentifier
            ));

            return;
        }

        $organization = $this->organizationRepository->findOneBy(['technicalId' => $organizationIdentifier]);
        if (!$organization) {
            $this->logger->warning(sprintf(
                'Ligne %d : Organisation non trouvée pour Identifiant %s',
                $lineNumber,
                $organizationIdentifier
            ));

            return;
        }

        if (!$contact->getOrganizations()->contains($organization)) {
            $contact->getOrganizations()->add($organization);
            $this->entityManager->persist($contact);

            $this->logger->info(sprintf(
                'Ligne %d : Relation Contact(%s) ➝ Organization(%s) ajoutée',
                $lineNumber,
                $ppIdentifier,
                $organizationIdentifier
            ));
        }

        ++$this->counter;

        if ($this->counter >= $this->batchSize) {
            $this->flushAndResetManager();
        }
    }
}
