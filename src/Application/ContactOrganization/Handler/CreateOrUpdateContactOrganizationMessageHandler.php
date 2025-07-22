<?php

namespace App\Application\ContactOrganization\Handler;

use App\Application\ContactOrganization\Message\ProcessChunkMessage;
use App\Application\ContactOrganization\Service\ContactOrganizationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateOrUpdateContactOrganizationMessageHandler
{
    public function __construct(
        private readonly ContactOrganizationManager $contactOrganizationManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessChunkMessage $message): void
    {
        $this->logger->info(sprintf(
            'Traitement du chunk #%d (taille : %d lignes)',
            $message->chunkNumber,
            count($message->chunk)
        ));

        foreach ($message->chunk as $lineNumber => $item) {
            try {
                $this->contactOrganizationManager->createOrUpdate(
                    $item['Identifiant PP'],
                    $item['Identifiant technique de la structure'],
                    $lineNumber
                );
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    'Erreur ligne %d dans le chunk #%d : %s',
                    $lineNumber,
                    $message->chunkNumber,
                    $e->getMessage()
                ));
            }
        }

        $this->contactOrganizationManager->flushAndResetManager();
        gc_collect_cycles();

        $this->logger->info(sprintf(
            'Chunk #%d terminé.',
            $message->chunkNumber
        ));
    }
}
