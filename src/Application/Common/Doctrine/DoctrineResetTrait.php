<?php

namespace App\Application\Common\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

trait DoctrineResetTrait
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;
    private LoggerInterface $logger;
    private int $counter = 0;

    public function flushAndResetManager(): void
    {
        try {
            $this->entityManager->flush();
            $this->entityManager->clear();

            $this->entityManager->getConnection()->close();

            $newManager = $this->registry->resetManager();

            if (!$newManager instanceof EntityManagerInterface) {
                throw new \LogicException('resetManager() did not return an EntityManagerInterface');
            }

            $this->entityManager = $newManager;

            gc_collect_cycles();
            $this->counter = 0;

            $this->logger->info('Flush & Reset effectué avec succès.');
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du flush/reset.', [
                'exception' => $e,
            ]);
        }
    }
}
