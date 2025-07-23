<?php

namespace App\Tests\Functional\Command;

use App\Entity\Contact;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateContactCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private EntityManagerInterface $entityManager;
    private string $testFilesDir;
    private string $fixturesDir;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('app:update-contact');
        $this->commandTester = new CommandTester($command);

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->testFilesDir = self::$kernel->getProjectDir().'/files';
        $this->fixturesDir = __DIR__.'/../../fixtures';

        $this->createTestDirectory();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        $this->cleanDatabase();
        parent::tearDown();
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processing Contacts', $output);
        $this->assertStringContainsString('Processing Organizations', $output);
        $this->assertStringContainsString('Processing Contact Organizations', $output);
        $this->assertStringContainsString('Deleting Contacts', $output);
    }

    public function testContactsAreImportedWithMessageConsumer(): void
    {
        // Arrange
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();
        $this->assertGreaterThan(0, count($contacts), 'At least some contacts should be imported after consuming messages');

        if (count($contacts) > 0) {
            $contact = $contacts[0];
            $this->assertNotEmpty($contact->familyName, 'Contact should have a family name');
        }
    }

    public function testOrganizationsAreImportedWithMessageConsumer(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $organizations = $this->entityManager->getRepository(Organization::class)->findAll();
        $this->assertGreaterThan(0, count($organizations), 'At least some organizations should be imported after consuming messages');

        if (count($organizations) > 0) {
            $organization = $organizations[0];
            $this->assertTrue(
                !empty($organization->technicalId) || !empty($organization->name),
                'Organization should have technical ID or name'
            );
        }
    }

    public function testProgressBarIsDisplayed(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('lignes traitées', $output);
        $this->assertStringContainsString('Chunk', $output);
        $this->assertStringContainsString('dispatché', $output);
    }

    public function testMemoryLimitIsSet(): void
    {
        $_ENV['APP_MEMORY_LIMIT'] = '256M';
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Limite mémoire fixée à 256M', $output);

        unset($_ENV['APP_MEMORY_LIMIT']);
    }

    public function testImportCountersAreDisplayed(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Created/Updated Contact:', $output);
        $this->assertStringContainsString('Created/Updated Organizations:', $output);
        $this->assertStringContainsString('Created/Updated Contact Organizations:', $output);
        $this->assertStringContainsString('Deleted Contact:', $output);
    }

    public function testDeleteContactsSection(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Deleting Contacts', $output);
        $this->assertStringContainsString('Deleting contacts', $output);
        $this->assertStringContainsString('Deleted Contact:', $output);
    }

    public function testChunkProcessingWorks(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Chunk', $output);
        $this->assertStringContainsString('dispatché', $output);

        if (false !== strpos($output, 'Created/Updated Contact: 1000')) {
            $this->assertStringContainsString('Chunk #1', $output);
        }
    }

    public function testAllThreeCsvFilesAreProcessed(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Updating contacts', $output);

        $this->assertStringContainsString('Updating organizations', $output);

        $this->assertStringContainsString('Updating contact_organizations', $output);
    }

    public function testWithMissingFiles(): void
    {
        $this->expectException(\Exception::class);
        $this->commandTester->execute([]);
    }

    /**
     * @throws \Exception
     */
    public function testDatabaseIsPopulatedAfterImportWithConsumer(): void
    {
        $this->copyFixturesToTestDir();

        $contactsBefore = $this->entityManager->getRepository(Contact::class)->findAll();
        $organizationsBefore = $this->entityManager->getRepository(Organization::class)->findAll();
        $this->assertEmpty($contactsBefore);
        $this->assertEmpty($organizationsBefore);

        $this->commandTester->execute([]);

        $contactsAfter = $this->entityManager->getRepository(Contact::class)->findAll();
        $organizationsAfter = $this->entityManager->getRepository(Organization::class)->findAll();

        $this->assertGreaterThan(0, count($contactsAfter), 'Contacts should be imported after consuming messages');
        $this->assertGreaterThan(0, count($organizationsAfter), 'Organizations should be imported after consuming messages');
    }

    public function testImportIsIdempotent(): void
    {
        $this->copyFixturesToTestDir();

        $this->commandTester->execute([]);
        $countAfterFirst = $this->entityManager->getRepository(Contact::class)->count([]);

        $this->commandTester->execute([]);
        $countAfterSecond = $this->entityManager->getRepository(Contact::class)->count([]);

        $this->assertEquals($countAfterFirst, $countAfterSecond, 'Import should be idempotent');
    }

    private function createTestDirectory(): void
    {
        if (!is_dir($this->testFilesDir)) {
            mkdir($this->testFilesDir, 0755, true);
        }
    }

    private function copyFixturesToTestDir(): void
    {
        $fixtures = [
            'valid_contacts.csv' => 'contacts.csv',
            'valid_organizations.csv' => 'organizations.csv',
            'valid_contacts_organization.csv' => 'contacts_organizations.csv',
        ];

        foreach ($fixtures as $source => $target) {
            $sourcePath = $this->fixturesDir.'/'.$source;
            $targetPath = $this->testFilesDir.'/'.$target;

            if (!file_exists($sourcePath)) {
                $this->markTestSkipped("Fixture file {$source} not found at {$sourcePath}");
            }

            copy($sourcePath, $targetPath);
        }
    }

    private function cleanDatabase(): void
    {
        try {
            $this->entityManager->getConnection()->executeStatement('DELETE FROM contact_organizations');
            $this->entityManager->getConnection()->executeStatement('DELETE FROM contact');
            $this->entityManager->getConnection()->executeStatement('DELETE FROM organization');
        } catch (\Exception $e) {
        }

        $this->entityManager->clear();
    }

    private function cleanupTestFiles(): void
    {
        $files = [
            $this->testFilesDir.'/contacts.csv',
            $this->testFilesDir.'/organizations.csv',
            $this->testFilesDir.'/contacts_organizations.csv',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
