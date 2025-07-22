<?php

namespace App\Entity\Trait;

use App\Entity\HashableInterface;

trait Hashable
{
    public function hash(): string
    {
        $checkString = '';

        $arr = (array) $this;
        ksort($arr);

        foreach ($arr as $k => $v) {
            $checkString .= sprintf('[%s:%s]', $k, $v);
        }

        return sha1($checkString);
    }

    public function identicalTo(HashableInterface $obj): bool
    {
        return $this->hash() === $obj->hash();
    }
}
