<?php

namespace App\Tests\unit\Application\Contact;

use App\Application\Contact\Service\ContactManager;
use App\Entity\Contact;
use App\Repository\ContactRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContactManagerTest extends TestCase
{
    private ContactManager $contactManager;
    private MockObject&ContactRepositoryInterface $contactRepository;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&ManagerRegistry $registry;
    private MockObject&LoggerInterface $logger;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->contactManager = new ContactManager(
            $this->contactRepository,
            $this->entityManager,
            $this->registry,
            $this->logger
        );
    }

    public function testCreateOrUpdateCreatesNewContact(): void
    {
        $contactData = [
            'Identifiant PP' => 'TEST123',
            "Type d'identifiant PP" => '1',
            'Libellé civilité' => 'M.',
            "Prénom d'exercice" => 'Jean',
            "Nom d'exercice" => 'Dupont',
        ];

        $this->contactRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'ppIdentifier' => 'TEST123',
                'ppIdentifierType' => 1,
            ])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Contact::class));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Contact créé'));

        $this->contactManager->createOrUpdate($contactData, 1);
    }

    public function testCreateOrUpdateUpdatesExistingContact(): void
    {
        $contactData = [
            'Identifiant PP' => 'TEST123',
            "Type d'identifiant PP" => '1',
            'Libellé civilité' => 'Mme',
            "Prénom d'exercice" => 'Marie',
            "Nom d'exercice" => 'Martin',
        ];

        $existingContact = new Contact();
        $existingContact->ppIdentifier = 'TEST123';
        $existingContact->ppIdentifierType = 1;

        $this->contactRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($existingContact);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Contact mis à jour'));

        $this->contactManager->createOrUpdate($contactData, 1);

        $this->assertEquals('Mme', $existingContact->title);
        $this->assertEquals('Marie', $existingContact->firstName);
        $this->assertEquals('Martin', $existingContact->familyName);
    }

    public function testCreateOrUpdateSkipsInvalidData(): void
    {
        $contactData = [
            'Identifiant PP' => '',
            "Type d'identifiant PP" => '1',
        ];

        $this->contactRepository
            ->expects($this->never())
            ->method('findOneBy');

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Identifiant PP ou type manquant'));

        $this->contactManager->createOrUpdate($contactData, 1);
    }

    public function testSoftDeleteNotUpdatedSince(): void
    {
        $threshold = new \DateTimeImmutable('2024-01-01');

        $contact1 = new Contact();
        $contact2 = new Contact();
        $contactsToDelete = [$contact1, $contact2];

        $this->contactRepository
            ->expects($this->once())
            ->method('findNotUpdatedSince')
            ->with($threshold)
            ->willReturn($contactsToDelete);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->contactManager->softDeleteNotUpdatedSince($threshold);

        $this->assertEquals(2, $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $contact1->deletedAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $contact2->deletedAt);
    }

    public function testFlushClearsEntityManager(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('clear');

        $this->contactManager->flush();
    }

    public function testCreateOrUpdateHandlesExceptions(): void
    {
        $this->contactRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willThrowException(new \Exception('Database error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur dans ContactManager::createOrUpdate'));

        $contactData = [
            'Identifiant PP' => 'TEST123',
            "Type d'identifiant PP" => '1',
        ];

        $this->contactManager->createOrUpdate($contactData, 1);
    }
}
