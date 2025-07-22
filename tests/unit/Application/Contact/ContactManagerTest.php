<?php

namespace App\Tests\unit\Application\Contact;

use App\Application\Contact\Service\ContactManager;
use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContactManagerTest extends TestCase
{
    /**
     * @var MockObject&ContactRepository
     */
    private ContactRepository $contactRepository;

    /**
     * @var MockObject&EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var MockObject&LoggerInterface
     */
    private LoggerInterface $logger;

    private ContactManager $contactManager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->contactRepository = $this->createMock(ContactRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->contactManager = new ContactManager(
            $this->contactRepository,
            $this->entityManager,
            $this->logger
        );
    }

    public function testCreateNewContactPersistsAndFlushes(): void
    {
        $contactData = [
            'Identifiant PP' => '12345',
            "Type d'identifiant PP" => '1',
            'Libellé civilité' => 'M.',
            "Prénom d'exercice" => 'John',
            "Nom d'exercice" => 'Doe',
        ];

        $this->contactRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Contact::class));

        $reflection = new \ReflectionClass($this->contactManager);
        $property = $reflection->getProperty('batchSize');
        $property->setValue($this->contactManager, 1);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('clear');

        $this->contactManager->createOrUpdate($contactData);
    }

    public function testUpdateExistingContactDoesNotPersistButFlushes(): void
    {
        $contact = new Contact();
        $contact->ppIdentifier = '12345';

        $contactData = [
            'Identifiant PP' => '12345',
            "Type d'identifiant PP" => '1',
            'Libellé civilité' => 'M.',
            "Prénom d'exercice" => 'Jane',
            "Nom d'exercice" => 'Smith',
        ];

        $this->contactRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($contact);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $reflection = new \ReflectionClass($this->contactManager);
        $property = $reflection->getProperty('batchSize');
        $property->setValue($this->contactManager, 1);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('clear');

        $this->contactManager->createOrUpdate($contactData);

        $this->assertEquals('Jane', $contact->firstName);
        $this->assertEquals('Smith', $contact->familyName);
    }

    public function testCreateOrUpdateLogsErrorAndContinues(): void
    {
        $contactData = [
            'Identifiant PP' => null,
            "Type d'identifiant PP" => null,
        ];

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Erreur dans ContactManager::createOrUpdate')
            );

        $this->contactManager->createOrUpdate($contactData);
    }

    public function testSoftDeleteNotUpdatedSinceWithContactsSuccess(): void
    {
        $threshold = new \DateTimeImmutable('-7 days');

        $contact1 = new Contact();
        $contact1->ppIdentifier = '11111';
        $contact1->firstName = 'John';
        $contact1->familyName = 'Doe';

        $contact2 = new Contact();
        $contact2->ppIdentifier = '22222';
        $contact2->firstName = 'Jane';
        $contact2->familyName = 'Smith';

        $contactsToDelete = [$contact1, $contact2];

        $this->contactRepository->expects($this->once())
            ->method('findNotUpdatedSince')
            ->with($threshold)
            ->willReturn($contactsToDelete);

        $persistedContacts = [];
        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($contact) use (&$persistedContacts) {
                $persistedContacts[] = $contact;
            });

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('clear');

        $result = $this->contactManager->softDeleteNotUpdatedSince($threshold);

        $this->assertEquals(2, $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $contact1->deletedAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $contact2->deletedAt);

        $this->assertContains($contact1, $persistedContacts);
        $this->assertContains($contact2, $persistedContacts);
    }

    public function testSoftDeleteNotUpdatedSinceWithNoContacts(): void
    {
        $threshold = new \DateTimeImmutable('-7 days');

        $this->contactRepository->expects($this->once())
            ->method('findNotUpdatedSince')
            ->with($threshold)
            ->willReturn([]);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('clear');

        $result = $this->contactManager->softDeleteNotUpdatedSince($threshold);

        $this->assertEquals(0, $result);
    }

    public function testSoftDeleteNotUpdatedSinceWithRepositoryException(): void
    {
        $threshold = new \DateTimeImmutable('-7 days');
        $exception = new \Exception('Database error');

        $this->contactRepository->expects($this->once())
            ->method('findNotUpdatedSince')
            ->with($threshold)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Erreur lors de la suppression des contacts.',
                ['exception' => $exception]
            );

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->contactManager->softDeleteNotUpdatedSince($threshold);

        $this->assertEquals(0, $result);
    }
}
