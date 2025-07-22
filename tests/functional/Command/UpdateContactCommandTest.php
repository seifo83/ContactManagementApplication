<?php

namespace App\Tests\functional\Command;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateContactCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->entityManager
            ->createQuery('DELETE FROM App\Entity\Contact')
            ->execute();
    }

    private function getFixturePath(string $fileName): string
    {
        $path = self::$kernel->getProjectDir().'/tests/fixtures/'.$fileName;

        if (!file_exists($path)) {
            throw new \RuntimeException(' fixture file not found: '.$path);
        }

        echo '📂 using fixture file: '.$path.PHP_EOL;

        return $path;
    }

    private function executeCommand(string $fixtureFile): string
    {
        $targetPath = self::$kernel->getProjectDir().'/files/contacts.csv';
        copy($fixtureFile, $targetPath);

        $application = new Application(self::$kernel);
        $command = $application->find('app:update-contact');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        return $commandTester->getDisplay();
    }

    public function testValidContactsCsv(): void
    {
        $fixtureFile = $this->getFixturePath('valid_contacts.csv');
        $output = $this->executeCommand($fixtureFile);

        echo "\n========= OUTPUT =========\n";
        echo $output;
        echo "\n==========================\n";

        $this->assertStringContainsString('Processing Contacts', $output);
        $this->assertStringContainsString('Created/Updated Contact: 10000', $output);

        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();
        $this->assertCount(10000, $contacts);
    }

    public function testDuplicateContactsCsv(): void
    {
        $fixtureFile = $this->getFixturePath('duplicate_contacts.csv');
        $output = $this->executeCommand($fixtureFile);

        echo "\n========= OUTPUT =========\n";
        echo $output;
        echo "\n==========================\n";

        $this->assertStringContainsString('Processing Contacts', $output);

        $this->entityManager->clear();
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();

        $this->assertLessThanOrEqual(10000, count($contacts));
    }

    public function testInvalidContactsCsv(): void
    {
        $fixtureFile = $this->getFixturePath('invalid_contacts.csv');
        $output = $this->executeCommand($fixtureFile);

        echo "\n========= OUTPUT =========\n";
        echo $output;
        echo "\n==========================\n";

        $this->assertStringContainsString('Processing Contacts', $output);

        $this->entityManager->clear();
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();

        $this->assertGreaterThan(0, count($contacts));
    }
}
