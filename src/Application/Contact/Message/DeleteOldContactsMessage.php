<?php

namespace App\Application\Contact\Message;

class DeleteOldContactsMessage
{
    private \DateTimeImmutable $threshold;

    public function __construct(?\DateTimeImmutable $threshold = null)
    {
        $this->threshold = $threshold ?? new \DateTimeImmutable('-1 week');
    }

    public function getThreshold(): \DateTimeImmutable
    {
        return $this->threshold;
    }
}
