<?php

namespace App\Tests\functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;

class UpdateContactCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private Filesystem $filesystem;
    private string $testFilesDir;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('app:update-contact');
        $this->commandTester = new CommandTester($command);

        $this->filesystem = new Filesystem();
        $this->testFilesDir = sys_get_temp_dir().'/test_csv_files';

        $this->filesystem->mkdir($this->testFilesDir);

        chdir($this->testFilesDir);

        $this->copyFixturesToTestDir();
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testFilesDir)) {
            $this->filesystem->remove($this->testFilesDir);
        }
    }

    public function testExecuteCommandSuccess(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Processing Contacts', $output);
        $this->assertStringContainsString('Deleting Contacts', $output);
        $this->assertStringContainsString('Processing Organizations', $output);
        $this->assertStringContainsString('Processing Contact Organizations', $output);

        $this->assertStringContainsString('Updating contacts', $output);
        $this->assertStringContainsString('Updating organizations', $output);
        $this->assertStringContainsString('Updating contact organizations', $output);

        $this->assertStringContainsString('Created/Updated Contact:', $output);
        $this->assertStringContainsString('Created/Updated Organizations:', $output);
        $this->assertStringContainsString('Created/Updated Contact Organizations:', $output);
    }

    public function testExecuteCommandWithMemoryLimit(): void
    {
        $_ENV['APP_MEMORY_LIMIT'] = '256M';

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Limite mémoire fixée à 256M', $output);

        unset($_ENV['APP_MEMORY_LIMIT']);
    }

    public function testExecuteCommandWithProgressBars(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Chunk #1 dispatché', $output);

        $this->assertMatchesRegularExpression('/Chunk #\d+ dispatché \(\d+ lignes\)/', $output);
    }

    public function testProcessContactsCountsCorrectly(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Created/Updated Contact:', $output);
        $this->assertStringContainsString('Created/Updated Organizations:', $output);
        $this->assertStringContainsString('Created/Updated Contact Organizations:', $output);

        $this->assertMatchesRegularExpression('/Created\/Updated Contact: \d+/', $output);
        $this->assertMatchesRegularExpression('/Created\/Updated Organizations: \d+/', $output);
        $this->assertMatchesRegularExpression('/Created\/Updated Contact Organizations: \d+/', $output);
    }

    public function testCommandHandlesMissingFiles(): void
    {
        $this->filesystem->remove($this->testFilesDir.'/files');

        $this->expectException(\League\Csv\UnavailableStream::class);
        $this->expectExceptionMessageMatches('/failed to open stream/');

        $this->commandTester->execute([]);
    }

    public function testMessengerIntegration(): void
    {
        $messenger = self::getContainer()->get('messenger.bus.default');
        $this->assertInstanceOf(MessageBusInterface::class, $messenger);

        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
    }

    private function copyFixturesToTestDir(): void
    {
        $fixturesDir = __DIR__.'/../../fixtures';
        $filesDir = $this->testFilesDir.'/files';
        $this->filesystem->mkdir($filesDir);

        $fixtures = [
            'valid_contacts.csv' => 'contacts.csv',
            'valid_organizations.csv' => 'organizations.csv',
            'valid_contacts_organization.csv' => 'contacts_organizations.csv',
        ];

        foreach ($fixtures as $source => $destination) {
            $sourcePath = $fixturesDir.'/'.$source;
            $destPath = $filesDir.'/'.$destination;

            if (file_exists($sourcePath)) {
                $this->filesystem->copy($sourcePath, $destPath);
            } else {
                $this->createDefaultCsvFile($destPath, $destination);
            }
        }
    }

    private function createDefaultCsvFile(string $path, string $filename): void
    {
        switch ($filename) {
            case 'contacts.csv':
                $content = "nom,prenom,email\nDupont,Jean,jean.dupont@example.com\n";
                break;
            case 'organizations.csv':
                $content = "nom,adresse\nEntreprise A,123 Rue de la Paix\n";
                break;
            case 'contacts_organizations.csv':
                $content = "Identifiant PP,Identifiant technique de la structure\ncontact_1,org_1\n";
                break;
            default:
                $content = "header1,header2\nvalue1,value2\n";
        }

        file_put_contents($path, $content);
    }
}
