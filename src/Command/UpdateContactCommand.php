<?php

namespace App\Command;

use App\Application\Contact\Message\CreateOrUpdateContactMessage;
use App\Application\Contact\Message\DeleteOldContactsMessage;
use App\Application\Organization\Message\CreateOrUpdateOrganizationMessage;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
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

    /**
     * @throws UnavailableStream
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Process contacts
        $this->io->section('Processing Contacts');
        $countContacts = $this->processContacts($output);

        $this->io->section('Deleting Contacts');
        $deleteContacts = $this->deleteContacts();

        $this->io->info('Created/Updated Contact: '.$countContacts);
        $this->io->info('Deleted Contact: '.$deleteContacts);

        // Process organizations
        // @TODO : Create or update organizations based on the CSV data
        $this->io->section('Processing Organizations');
        $countOrganizations = $this->processOrganizations($output);
        $this->io->info('Created/Updated Organizations: '.$countOrganizations);
        $this->io->info('Deleted Organizations: 0');

        // Process contact organizations
        // @TODO : Create or update contact organizations based on the CSV data
        $this->io->section('Processing Contact Organizations');
        $this->io->info('Created/Updated Contact Organizations: 0');
        $this->io->info('Deleted Contact Organizations: 0');

        return Command::SUCCESS;
    }

    /**
     * @throws UnavailableStream
     * @throws Exception
     * @throws ExceptionInterface
     */
    private function processContacts(OutputInterface $output): int
    {
        $this->io->writeln('Updating contacts');
        $progress = new ProgressBar($output);
        $progress->setFormat('debug_nomax');

        $count = 0;
        $batchSize = 100;
        $reader = Reader::createFromPath('files/contacts.csv');

        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        foreach ($records as $i => $item) {
            $progress->advance();

            // @TODO : Create or update contact based on the CSV data

            $this->messageBus->dispatch(
                new CreateOrUpdateContactMessage($item)
            );

            ++$count;

            if (0 === $count % $batchSize) {
                gc_collect_cycles();
            }
        }

        $progress->finish();

        return $count;
    }

    private function deleteContacts(): int
    {
        $this->io->writeln('Deleting contacts');

        // @TODO : Create a query to soft delete contacts if updated_at has not changed since 1 week

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

    /**
     * @throws UnavailableStream
     * @throws Exception
     */
    private function processOrganizations(OutputInterface $output): int
    {
        $this->io->writeln('Updating organizations');
        $progress = new ProgressBar($output);
        $progress->setFormat('debug_nomax');

        $count = 0;
        $batchSize = 100;
        $reader = Reader::createFromPath('files/organizations.csv');

        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        foreach ($records as $lineNumber => $item) {
            $progress->advance();

            try {
                $this->messageBus->dispatch(
                    new CreateOrUpdateOrganizationMessage($item, $lineNumber + 1)
                );
                ++$count;
            } catch (\Throwable $e) {
                $this->io->error(sprintf(
                    'Erreur ligne %d (organizations.csv) : %s',
                    $lineNumber + 1,
                    $e->getMessage()
                ));
            }

            if (0 === $count % $batchSize) {
                gc_collect_cycles();
            }
        }

        $progress->finish();

        return $count;
    }
}
