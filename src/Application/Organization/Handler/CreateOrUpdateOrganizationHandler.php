<?php

namespace App\Application\Organization\Handler;

use App\Application\Organization\Message\CreateOrUpdateOrganizationMessage;
use App\Application\Organization\Service\OrganizationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateOrUpdateOrganizationHandler
{
    public function __construct(
        private readonly OrganizationManager $organizationManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateOrUpdateOrganizationMessage $message): void
    {
        try {
            $this->organizationManager->createOrUpdateOrganization($message->organization, $message->lineNumber);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création/mise à jour d’une organization.', [
                'exception' => $e,
                'organization' => $message->organization,
                'line' => $message->lineNumber,
            ]);
        }
    }

    public function __destruct()
    {
        try {
            $this->organizationManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du flush final.', [
                'exception' => $e,
            ]);
        }
    }
}
