<?php

namespace App\Application\ContactOrganization\Service;

use App\Repository\ContactRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class ContactOrganizationManager
{
    private int $batchSize = 100;
    private int $counter = 0;

    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly OrganizationRepository $organizationRepository,
        private EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly ManagerRegistry $registry,
    ) {
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

    public function flushAndResetManager(): void
    {
        try {
            $this->entityManager->flush();
            $this->entityManager->clear();

            $this->entityManager->getConnection()->close();

            $newManager = $this->registry->resetManager();

            if (!$newManager instanceof EntityManagerInterface) {
                throw new \LogicException('resetManager() did not return an EntityManagerInterface');
            }

            $this->entityManager = $newManager;

            gc_collect_cycles();
            $this->counter = 0;

            $this->logger->info('Flush & Reset effectué avec succès.');
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du flush/reset.', [
                'exception' => $e,
            ]);
        }
    }
}
