<?php

namespace App\Application\Common\Message;

class ProcessChunkMessage
{
    /**
     * @param list<array<string, mixed>> $chunk
     */
    public function __construct(
        public readonly array $chunk,
        public readonly int $chunkNumber,
        public readonly string $type,
    ) {
    }
}
