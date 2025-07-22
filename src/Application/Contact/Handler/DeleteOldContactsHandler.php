<?php

namespace App\Application\Contact\Handler;

use App\Application\Contact\Message\DeleteOldContactsMessage;
use App\Application\Contact\Service\ContactManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteOldContactsHandler
{
    public function __construct(private readonly ContactManager $contactManager)
    {
    }

    public function __invoke(DeleteOldContactsMessage $message): int
    {
        return $this->contactManager->softDeleteNotUpdatedSince(
            $message->getThreshold()
        );
    }
}
