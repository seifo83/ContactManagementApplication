<?php

namespace App\Tests\unit\Repository;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ContactRepositoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testFindNotUpdatedSinceCallsCorrectMethods(): void
    {
        $threshold = new \DateTimeImmutable('2024-01-15 10:00:00');
        $expectedResult = [new Contact(), new Contact()];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedResult);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('c.updatedAt < :threshold')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('c.deletedAt IS NULL')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('threshold', $threshold)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder(ContactRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($queryBuilder);

        $result = $repository->findNotUpdatedSince($threshold);

        $this->assertSame($expectedResult, $result);
        $this->assertCount(2, $result);
    }

    /**
     * @throws Exception
     */
    public function testFindNotUpdatedSinceReturnsEmptyArray(): void
    {
        $threshold = new \DateTimeImmutable('2024-01-15 10:00:00');

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $repository = $this->getMockBuilder(ContactRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        $result = $repository->findNotUpdatedSince($threshold);

        $this->assertEmpty($result);
    }
}
