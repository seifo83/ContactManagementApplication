<?php

namespace App\Tests\unit\Application\Contact;

use App\Application\Contact\Handler\CreateOrUpdateContactHandler;
use App\Application\Contact\Message\CreateOrUpdateContactMessage;
use App\Application\Contact\Service\ContactManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreateOrUpdateContactHandlerTest extends TestCase
{
    /**
     * @var MockObject&ContactManager
     */
    private ContactManager $contactManager;

    /**
     * @var MockObject&LoggerInterface
     */
    private LoggerInterface $logger;

    private CreateOrUpdateContactHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->contactManager = $this->createMock(ContactManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new CreateOrUpdateContactHandler(
            $this->contactManager,
            $this->logger
        );
    }

    public function testHandlerCallsCreateOrUpdate(): void
    {
        $contactData = [
            'Identifiant PP' => '12345',
            "Type d'identifiant PP" => '1',
            'Libellé civilité' => 'M.',
            "Prénom d'exercice" => 'John',
            "Nom d'exercice" => 'Doe',
        ];

        $message = new CreateOrUpdateContactMessage($contactData);

        $this->contactManager
            ->expects($this->once())
            ->method('createOrUpdate')
            ->with($contactData);

        $this->handler->__invoke($message);
    }

    public function testFlushIsCalledAfterInterval(): void
    {
        $contactData = [
            'Identifiant PP' => '12345',
            "Type d'identifiant PP" => '1',
            'Libellé civilité' => 'M.',
            "Prénom d'exercice" => 'John',
            "Nom d'exercice" => 'Doe',
        ];

        $message = new CreateOrUpdateContactMessage($contactData);

        $reflection = new \ReflectionClass($this->handler);
        $property = $reflection->getProperty('flushInterval');
        $property->setValue($this->handler, 1);

        $this->contactManager
            ->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($message);
    }

    public function testHandlerLogsErrorAndContinues(): void
    {
        $contactData = [
            'Identifiant PP' => '12345',
            "Type d'identifiant PP" => '1',
        ];

        $message = new CreateOrUpdateContactMessage($contactData);

        $this->contactManager
            ->method('createOrUpdate')
            ->willThrowException(new \RuntimeException('Erreur simulée'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Erreur lors du traitement du contact.')
            );

        $this->handler->__invoke($message);
    }
}
