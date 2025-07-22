<?php

namespace App\Entity;

interface HashableInterface
{
    public function hash(): string;

    public function identicalTo(HashableInterface $obj): bool;
}
