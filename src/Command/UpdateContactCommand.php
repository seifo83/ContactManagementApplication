<?php

namespace App\Command;

use App\Application\Common\Message\ProcessChunkMessage;
use App\Application\Contact\Message\DeleteOldContactsMessage;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsCommand(
    name: 'app:update-contact',
    description: 'Update all contacts and their organizations from CSV files',
)]
class UpdateContactCommand extends Command
{
    private SymfonyStyle $io;
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        parent::__construct();
        $this->messageBus = $messageBus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $memoryLimit = $_ENV['APP_MEMORY_LIMIT'] ?? null;
        if ($memoryLimit) {
            ini_set('memory_limit', $memoryLimit);
            $this->io->note(sprintf('Limite mémoire fixée à %s', $memoryLimit));
        }

        // Process contacts
        $this->io->section('Processing Contacts');
        $countContacts = $this->processContacts($output);

        $this->io->section('Deleting Contacts');
        $deleteContacts = $this->deleteContacts();

        $this->io->info('Created/Updated Contact: '.$countContacts);
        $this->io->info('Deleted Contact: '.$deleteContacts);

        // Process organizations
        $this->io->section('Processing Organizations');
        $countOrganizations = $this->processOrganizations($output);
        $this->io->info('Created/Updated Organizations: '.$countOrganizations);

        // Process contact organizations
        $this->io->section('Processing Contact Organizations');
        $countContactOrganizations = $this->processContactOrganizations($output);
        $this->io->info('Created/Updated Contact Organizations: '.$countContactOrganizations);

        return Command::SUCCESS;
    }

    /**
     * @throws ReaderNotOpenedException
     * @throws IOException
     */
    private function processContacts(OutputInterface $output): int
    {
        return $this->processCsvFile('files/contacts.csv', 'contacts', $output);
    }

    /**
     * @throws ReaderNotOpenedException
     * @throws IOException
     */
    private function processOrganizations(OutputInterface $output): int
    {
        return $this->processCsvFile('files/organizations.csv', 'organizations', $output);
    }

    /**
     * @throws ReaderNotOpenedException
     * @throws IOException
     */
    private function processContactOrganizations(OutputInterface $output): int
    {
        return $this->processCsvFile('files/contacts_organizations.csv', 'contact_organizations', $output);
    }

    /**
     * @throws ReaderNotOpenedException
     * @throws IOException
     */
    private function processCsvFile(string $filePath, string $type, OutputInterface $output): int
    {
        $this->io->writeln("Updating {$type}");

        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open($filePath);

        $chunkSize = 1000;
        $chunk = [];
        $chunkNumber = 0;
        $count = 0;
        $headers = [];

        $progress = new ProgressBar($output);
        $progress->setFormat(' %current% lignes traitées | %elapsed% | %memory% | %message%');
        $progress->start();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $rowData = $row->toArray();

                if (1 === $rowIndex) {
                    $headers = $rowData;
                    continue;
                }

                $record = array_combine($headers, $rowData);
                $chunk[] = $record;

                if (count($chunk) >= $chunkSize) {
                    $this->dispatchChunk($chunk, $chunkNumber, $type, $progress);
                    $count += count($chunk);

                    $chunk = [];
                    ++$chunkNumber;

                    $progress->setMessage(sprintf(
                        'Chunk #%d dispatché (%d lignes traitées)',
                        $chunkNumber,
                        $count
                    ));
                    $progress->advance($chunkSize);
                }
            }
        }

        if (!empty($chunk)) {
            $this->dispatchChunk($chunk, $chunkNumber, $type, $progress);
            $count += count($chunk);
            $progress->advance(count($chunk));
        }

        $reader->close();
        $progress->finish();
        $this->io->newLine(2);

        return $count;
    }

    /**
     * @param array<array<string, mixed>> $chunk
     */
    private function dispatchChunk(array $chunk, int $chunkNumber, string $type, ProgressBar $progress): void
    {
        try {
            $this->messageBus->dispatch(
                new ProcessChunkMessage($chunk, $chunkNumber, $type)
            );
        } catch (\Throwable $e) {
            $this->io->warning(sprintf(
                'Erreur lors du dispatch du chunk #%d (%s) : %s',
                $chunkNumber + 1,
                $type,
                $e->getMessage()
            ));
        }
    }

    private function deleteContacts(): int
    {
        $this->io->writeln('Deleting contacts');

        try {
            $envelope = $this->messageBus->dispatch(new DeleteOldContactsMessage());

            /** @var HandledStamp|null $handled */
            $handled = $envelope->last(HandledStamp::class);

            if (null !== $handled) {
                return $handled->getResult();
            }
        } catch (\Throwable $e) {
            $this->io->error('Erreur lors de la suppression des anciens contacts : '.$e->getMessage());
        }

        return 0;
    }
}
