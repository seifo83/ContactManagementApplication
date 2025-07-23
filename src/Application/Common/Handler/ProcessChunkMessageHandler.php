<?php

namespace App\Application\Common\Handler;

use App\Application\Common\Message\ProcessChunkMessage;
use App\Application\Contact\Service\ContactManager;
use App\Application\ContactOrganization\Service\ContactOrganizationManager;
use App\Application\Organization\Service\OrganizationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessChunkMessageHandler
{
    public function __construct(
        private readonly ContactManager $contactManager,
        private readonly OrganizationManager $organizationManager,
        private readonly ContactOrganizationManager $contactOrganizationManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessChunkMessage $message): void
    {
        $this->logger->info(sprintf(
            'Traitement du chunk #%d (%s, %d lignes)',
            $message->chunkNumber,
            $message->type,
            count($message->chunk)
        ));

        try {
            match ($message->type) {
                'contacts' => $this->processContacts($message->chunk, $message->chunkNumber),
                'organizations' => $this->processOrganizations($message->chunk, $message->chunkNumber),
                'contact_organizations' => $this->processContactOrganizations($message->chunk, $message->chunkNumber),
                default => throw new \InvalidArgumentException("Type de chunk inconnu : {$message->type}"),
            };
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Erreur dans le chunk #%d (%s) : %s',
                $message->chunkNumber,
                $message->type,
                $e->getMessage()
            ));
        }

        $this->logger->info(sprintf(
            'Chunk #%d (%s) terminé.',
            $message->chunkNumber,
            $message->type
        ));
    }

    /**
     * @param list<array<string, mixed>> $chunk
     */
    private function processContacts(array $chunk, int $chunkNumber): void
    {
        foreach ($chunk as $index => $item) {
            $lineNumber = ($chunkNumber * count($chunk)) + $index + 1;
            try {
                $this->contactManager->createOrUpdate($item, $lineNumber);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    '[Contacts] Erreur ligne %d (chunk #%d) : %s',
                    $lineNumber,
                    $chunkNumber,
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $chunk
     */
    private function processOrganizations(array $chunk, int $chunkNumber): void
    {
        foreach ($chunk as $index => $item) {
            $lineNumber = ($chunkNumber * count($chunk)) + $index + 1;
            try {
                $this->organizationManager->createOrUpdateOrganization($item, $lineNumber);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    '[Organizations] Erreur ligne %d (chunk #%d) : %s',
                    $lineNumber,
                    $chunkNumber,
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $chunk
     */
    private function processContactOrganizations(array $chunk, int $chunkNumber): void
    {
        foreach ($chunk as $index => $item) {
            $lineNumber = ($chunkNumber * count($chunk)) + $index + 1;
            try {
                $this->contactOrganizationManager->createOrUpdate(
                    $item['Identifiant PP'],
                    $item['Identifiant technique de la structure'],
                    $lineNumber
                );
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    '[ContactOrganizations] Erreur ligne %d (chunk #%d) : %s',
                    $lineNumber,
                    $chunkNumber,
                    $e->getMessage()
                ));
            }
        }
    }
}
