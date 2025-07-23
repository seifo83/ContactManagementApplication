<?php

namespace App\Application\Contact\Service;

use App\Application\Common\Doctrine\DoctrineResetTrait;
use App\Entity\Contact;
use App\Repository\ContactRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class ContactManager
{
    use DoctrineResetTrait;

    private int $batchSize = 20;
    private int $counter = 0;

    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry,
        LoggerInterface $logger,
    ) {
        $this->entityManager = $entityManager;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * @param array<string, mixed> $contactData
     */
    public function createOrUpdate(array $contactData, int $lineNumber): void
    {
        try {
            if (empty($contactData['Identifiant PP']) || empty($contactData["Type d'identifiant PP"])) {
                $this->logger->warning(sprintf(
                    'Ligne %d : Identifiant PP ou type manquant.',
                    $lineNumber
                ));

                return;
            }

            $contact = $this->contactRepository->findOneBy([
                'ppIdentifier' => $contactData['Identifiant PP'],
                'ppIdentifierType' => (int) $contactData["Type d'identifiant PP"],
            ]);

            if (!$contact) {
                $contact = new Contact();
                $contact->ppIdentifier = $contactData['Identifiant PP'];
                $contact->ppIdentifierType = (int) $contactData["Type d'identifiant PP"];
                $contact->title = $contactData['Libellé civilité'] ?? '';
                $contact->firstName = $contactData["Prénom d'exercice"] ?? '';
                $contact->familyName = $contactData["Nom d'exercice"] ?? '';
                $this->entityManager->persist($contact);

                $this->logger->info(sprintf(
                    'Ligne %d : Contact créé (PP: %s)',
                    $lineNumber,
                    $contactData['Identifiant PP']
                ));
            } else {
                $contact->title = $contactData['Libellé civilité'] ?? '';
                $contact->firstName = $contactData["Prénom d'exercice"] ?? '';
                $contact->familyName = $contactData["Nom d'exercice"] ?? '';

                $this->logger->info(sprintf(
                    'Ligne %d : Contact mis à jour (PP: %s)',
                    $lineNumber,
                    $contactData['Identifiant PP']
                ));
            }

            ++$this->counter;

            if ($this->counter >= $this->batchSize) {
                $this->flushAndResetManager();
            }
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Ligne %d : Erreur dans ContactManager::createOrUpdate.',
                $lineNumber
            ), [
                'exception' => $e,
                'contact' => $contactData,
            ]);
        }
    }

    public function softDeleteNotUpdatedSince(\DateTimeImmutable $threshold): int
    {
        try {
            $contactsToDelete = $this->contactRepository->findNotUpdatedSince($threshold);

            foreach ($contactsToDelete as $contact) {
                $contact->deletedAt = new \DateTimeImmutable();
                $this->entityManager->persist($contact);
            }

            $this->flush();

            return count($contactsToDelete);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la suppression des contacts.', [
                'exception' => $e,
            ]);

            return 0;
        }
    }

    public function flush(): void
    {
        try {
            $this->entityManager->flush();
            $this->entityManager->clear();
            $this->counter = 0;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du flush des contacts.', [
                'exception' => $e,
            ]);
        }
    }
}
