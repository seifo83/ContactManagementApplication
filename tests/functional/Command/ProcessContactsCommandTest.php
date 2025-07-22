<?php

namespace App\Tests\functional\Command;

use App\Application\Contact\Handler\DeleteOldContactsHandler;
use App\Application\Contact\Message\DeleteOldContactsMessage;
use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessContactsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->resetDatabase();
    }

    private function resetDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Contact')->execute();
    }

    private function loadContactsFixture(string $fixtureFile): void
    {
        $this->resetDatabase();

        $targetPath = self::$kernel->getProjectDir().'/files/contacts.csv';
        copy($fixtureFile, $targetPath);

        $application = new Application(self::$kernel);
        $command = $application->find('app:update-contact');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    public function testProcessContactsPersistsData(): void
    {
        $fixtureFile = self::$kernel->getProjectDir().'/tests/fixtures/valid_contacts.csv';
        $this->loadContactsFixture($fixtureFile);

        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();
        $this->assertGreaterThan(0, count($contacts), 'Contacts should be persisted in DB');

        /** @var Contact $contact */
        $contact = $contacts[0];
        $this->assertNotEmpty($contact->familyName, 'Contact familyName should not be empty');
        $this->assertNotNull($contact->firstName, 'Contact firstName can be null but checked for test');

        $this->assertContains(
            $contact->title,
            ['M.', 'Mme', 'Dr', 'Pr', null],
            'Contact title should be valid'
        );
    }

    public function testDeleteOldContactsRemovesExpiredContacts(): void
    {
        $fixtureFile = self::$kernel->getProjectDir().'/tests/fixtures/delete_contacts.csv';
        $this->loadContactsFixture($fixtureFile);

        $oldContacts = $this->entityManager->getRepository(Contact::class)->findBy([], null, 5);
        foreach ($oldContacts as $contact) {
            $contact->updatedAt = (new \DateTimeImmutable())->modify('-2 years');
            $this->entityManager->persist($contact);
        }
        $this->entityManager->flush();

        $this->assertCount(20, $this->entityManager->getRepository(Contact::class)->findAll());

        $threshold = (new \DateTimeImmutable())->modify('-1 year');
        $handler = static::getContainer()->get(DeleteOldContactsHandler::class);
        $deletedCount = $handler(new DeleteOldContactsMessage($threshold));

        $this->assertEquals(5, $deletedCount, '5 anciens contacts doivent être supprimés');

        $remainingContacts = $this->entityManager->getRepository(Contact::class)->findBy(['deletedAt' => null]);
        $this->assertCount(15, $remainingContacts, 'Il doit rester 15 contacts actifs après suppression');
    }
}
