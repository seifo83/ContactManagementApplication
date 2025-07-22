<?php

namespace App\Application\Contact\Handler;

use App\Application\Contact\Message\CreateOrUpdateContactMessage;
use App\Application\Contact\Service\ContactManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateOrUpdateContactHandler
{
    private static int $messageCount = 0;
    private static int $flushInterval = 50;

    public function __construct(
        private readonly ContactManager $contactManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateOrUpdateContactMessage $message): void
    {
        $contactData = $message->getContact();

        try {
            $this->contactManager->createOrUpdate($contactData);
            ++self::$messageCount;

            if (0 === self::$messageCount % self::$flushInterval) {
                $this->contactManager->flush();
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du traitement du contact.', [
                'exception' => $e,
                'contact' => $contactData,
            ]);
        }
    }

    public function __destruct()
    {
        try {
            $this->contactManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du flush final.', [
                'exception' => $e,
            ]);
        }
    }
}
