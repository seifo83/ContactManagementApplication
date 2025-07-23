<?php

namespace App\Tests\unit\Application\Common\Handler;

use App\Application\Common\Handler\ProcessChunkMessageHandler;
use App\Application\Common\Message\ProcessChunkMessage;
use App\Application\Contact\Service\ContactManager;
use App\Application\ContactOrganization\Service\ContactOrganizationManager;
use App\Application\Organization\Service\OrganizationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProcessChunkMessageHandlerTest extends TestCase
{
    private MockObject&ContactManager $contactManager;
    private MockObject&OrganizationManager $organizationManager;
    private MockObject&ContactOrganizationManager $contactOrganizationManager;
    private MockObject&LoggerInterface $logger;
    private ProcessChunkMessageHandler $handler;

    protected function setUp(): void
    {
        $this->contactManager = $this->createMock(ContactManager::class);
        $this->organizationManager = $this->createMock(OrganizationManager::class);
        $this->contactOrganizationManager = $this->createMock(ContactOrganizationManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ProcessChunkMessageHandler(
            $this->contactManager,
            $this->organizationManager,
            $this->contactOrganizationManager,
            $this->logger
        );
    }

    public function testProcessContactsChunk(): void
    {
        $chunk = [
            ['nom' => 'Dupont', 'prenom' => 'Jean'],
            ['nom' => 'Martin', 'prenom' => 'Paul'],
        ];

        $message = new ProcessChunkMessage($chunk, 1, 'contacts');

        $this->contactManager
            ->expects($this->exactly(2))
            ->method('createOrUpdate')
            ->willReturnCallback(function ($data, $lineNumber) use ($chunk) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertEquals($chunk[0], $data);
                    $this->assertEquals(1, $lineNumber);
                } elseif (2 === $callCount) {
                    $this->assertEquals($chunk[1], $data);
                    $this->assertEquals(2, $lineNumber);
                }
            });

        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message) {
                static $logCallCount = 0;
                ++$logCallCount;

                if (1 === $logCallCount) {
                    $this->assertStringContainsString('Traitement du chunk #1 (contacts, 2 lignes)', $message);
                } elseif (2 === $logCallCount) {
                    $this->assertStringContainsString('Chunk #1 (contacts) terminé.', $message);
                }
            });

        ($this->handler)($message);
    }

    public function testProcessOrganizationsChunk(): void
    {
        $chunk = [
            ['nom' => 'Entreprise A'],
            ['nom' => 'Entreprise B'],
        ];

        $message = new ProcessChunkMessage($chunk, 2, 'organizations');

        $this->organizationManager
            ->expects($this->exactly(2))
            ->method('createOrUpdateOrganization')
            ->willReturnCallback(function ($data, $lineNumber) use ($chunk) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertEquals($chunk[0], $data);
                    $this->assertEquals(3, $lineNumber);
                } elseif (2 === $callCount) {
                    $this->assertEquals($chunk[1], $data);
                    $this->assertEquals(4, $lineNumber);
                }
            });

        ($this->handler)($message);
    }

    public function testProcessContactOrganizationsChunk(): void
    {
        $chunk = [
            [
                'Identifiant PP' => 'contact123',
                'Identifiant technique de la structure' => 'org456',
            ],
        ];

        $message = new ProcessChunkMessage($chunk, 0, 'contact_organizations');

        $this->contactOrganizationManager
            ->expects($this->once())
            ->method('createOrUpdate')
            ->with('contact123', 'org456', 1);

        ($this->handler)($message);
    }

    public function testUnknownChunkTypeThrowsException(): void
    {
        $chunk = [['data' => 'test']];
        $message = new ProcessChunkMessage($chunk, 1, 'unknown_type');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur dans le chunk #1 (unknown_type)'));

        ($this->handler)($message);
    }

    public function testContactManagerExceptionIsLogged(): void
    {
        $chunk = [['nom' => 'Test']];
        $message = new ProcessChunkMessage($chunk, 1, 'contacts');

        $this->contactManager
            ->method('createOrUpdate')
            ->willThrowException(new \Exception('Erreur de test'));

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('[Contacts] Erreur ligne 1'));

        ($this->handler)($message);
    }

    public function testCalculatesCorrectLineNumbers(): void
    {
        $chunk = [
            ['nom' => 'Test1'],
            ['nom' => 'Test2'],
        ];

        $message = new ProcessChunkMessage($chunk, 2, 'contacts');

        $this->contactManager
            ->expects($this->exactly(2))
            ->method('createOrUpdate')
            ->willReturnCallback(function ($data, $lineNumber) use ($chunk) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertEquals($chunk[0], $data);
                    $this->assertEquals(5, $lineNumber);
                } elseif (2 === $callCount) {
                    $this->assertEquals($chunk[1], $data);
                    $this->assertEquals(6, $lineNumber);
                }
            });

        ($this->handler)($message);
    }
}
