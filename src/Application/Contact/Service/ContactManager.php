<?php

namespace App\Application\Contact\Service;

use App\Entity\Contact;
use App\Repository\ContactRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ContactManager
{
    private int $batchSize = 20;
    private int $counter = 0;

    public function __construct(
        private readonly ContactRepositoryInterface $contactRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $contactData
     */
    public function createOrUpdate(array $contactData): void
    {
        try {
            if (empty($contactData['Identifiant PP']) || empty($contactData["Type d'identifiant PP"])) {
                throw new \InvalidArgumentException('Identifiant PP ou type manquant.');
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
            } else {
                $contact->title = $contactData['Libellé civilité'] ?? '';
                $contact->firstName = $contactData["Prénom d'exercice"] ?? '';
                $contact->familyName = $contactData["Nom d'exercice"] ?? '';
            }

            ++$this->counter;
            if (0 === $this->counter % $this->batchSize) {
                $this->flush();
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur dans ContactManager::createOrUpdate.', [
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
