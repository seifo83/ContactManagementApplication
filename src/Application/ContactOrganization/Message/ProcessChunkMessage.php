<?php

namespace App\Application\ContactOrganization\Message;

class ProcessChunkMessage
{
    /**
     * @param array<int, array<string, mixed>> $chunk
     */
    public function __construct(
        public readonly array $chunk,
        public readonly int $chunkNumber,
    ) {
    }
}
