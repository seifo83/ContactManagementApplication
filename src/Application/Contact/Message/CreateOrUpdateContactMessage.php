<?php

namespace App\Application\Contact\Message;

class CreateOrUpdateContactMessage
{
    /**
     * @param array<string, mixed> $contact
     */
    public function __construct(
        public readonly array $contact,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getContact(): array
    {
        return $this->contact;
    }
}
