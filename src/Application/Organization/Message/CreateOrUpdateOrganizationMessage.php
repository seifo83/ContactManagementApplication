<?php

namespace App\Application\Organization\Message;

class CreateOrUpdateOrganizationMessage
{
    /**
     * @param array<string, mixed> $organization
     */
    public function __construct(
        public readonly array $organization,
        public readonly int $lineNumber,
    ) {
    }
}
