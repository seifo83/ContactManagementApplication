<?php

namespace App\Tests\unit\Application\Organization;

use App\Application\Organization\Service\OrganizationManager;
use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrganizationManagerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCreateOrUpdateOrganizationCreatesNewOrganization(): void
    {
        $organizationData = [
            'Identifiant technique de la structure' => 'ORG123',
            'Raison sociale site' => 'Hôpital Central',
            'Adresse e-mail (coord. structure)' => 'contact@hopital.fr',
            'Téléphone (coord. structure)' => '0123456789',
        ];

        $repository = $this->createMock(OrganizationRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['technicalId' => 'ORG123'])
            ->willReturn(null);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Organization::class));

        $manager = new OrganizationManager($repository, $entityManager, $logger);
        $manager->createOrUpdateOrganization($organizationData, 1);
    }

    /**
     * @throws Exception
     */
    public function testCreateOrUpdateOrganizationUpdatesExistingOrganization(): void
    {
        $organizationData = [
            'Identifiant technique de la structure' => 'ORG456',
            'Raison sociale site' => 'Clinique Privée',
        ];

        $existingOrganization = new Organization();
        $existingOrganization->technicalId = 'ORG456';

        $repository = $this->createMock(OrganizationRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['technicalId' => 'ORG456'])
            ->willReturn($existingOrganization);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Organization::class));

        $manager = new OrganizationManager($repository, $entityManager, $logger);
        $manager->createOrUpdateOrganization($organizationData, 2);

        $this->assertSame('Clinique Privée', $existingOrganization->name);
    }

    /**
     * @throws Exception
     */
    public function testFlushIsCalledAfterBatchSize(): void
    {
        $repository = $this->createMock(OrganizationRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $entityManager->expects($this->once())->method('flush');
        $entityManager->expects($this->once())->method('clear');

        $manager = new OrganizationManager($repository, $entityManager, $logger);

        $orgDataTemplate = [
            'Identifiant technique de la structure' => 'ORG',
            'Raison sociale site' => 'Test Org',
        ];

        for ($i = 1; $i <= 100; ++$i) {
            $orgData = $orgDataTemplate;
            $orgData['Identifiant technique de la structure'] .= $i;

            $repository->method('findOneBy')->willReturn(null);
            $entityManager->method('persist')->with($this->isInstanceOf(Organization::class));

            $manager->createOrUpdateOrganization($orgData, $i);
        }
    }

    /**
     * @throws Exception
     */
    public function testCreateOrUpdateOrganizationLogsOnError(): void
    {
        $organizationData = [
            'Identifiant technique de la structure' => '',
        ];

        $repository = $this->createMock(OrganizationRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur à la ligne'));

        $manager = new OrganizationManager($repository, $entityManager, $logger);
        $manager->createOrUpdateOrganization($organizationData, 42);
    }
}
