<?php

namespace App\Application\Organization\Service;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrganizationManager
{
    private int $batchSize = 100;
    private int $counter = 0;

    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $organizationData
     */
    public function createOrUpdateOrganization(array $organizationData, int $lineNumber): void
    {
        try {
            if (
                empty($organizationData['Identifiant technique de la structure'])
                || empty($organizationData['Raison sociale site'])
            ) {
                throw new \InvalidArgumentException(sprintf('Colonnes manquantes (ligne %d) : Identifiant technique ou Raison sociale site absent.', $lineNumber));
            }

            $organization = $this->organizationRepository->findOneBy([
                'technicalId' => $organizationData['Identifiant technique de la structure'],
            ]);

            if (!$organization) {
                $organization = new Organization();
                $organization->technicalId = $organizationData['Identifiant technique de la structure'];
            }

            $organization->name = $organizationData['Raison sociale site'];
            $organization->emailAddress = $organizationData['Adresse e-mail (coord. structure)'] ?? null;
            $organization->phoneNumber = $organizationData['Téléphone (coord. structure)'] ?? null;

            $this->entityManager->persist($organization);

            ++$this->counter;

            if ($this->counter >= $this->batchSize) {
                $this->flush();
            }
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Erreur à la ligne %d lors du traitement de l’organisation : %s',
                $lineNumber,
                $e->getMessage()
            ), [
                'line' => $lineNumber,
                'data' => $organizationData,
            ]);
        }
    }

    public function flush(): void
    {
        try {
            $this->entityManager->flush();
            $this->entityManager->clear();
            $this->counter = 0;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du flush des organizations.', [
                'exception' => $e,
            ]);
        }
    }
}
