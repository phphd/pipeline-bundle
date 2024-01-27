<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Message;

use PhPhD\Pipeline\NextForwarded;

#[NextForwarded]
final class Ping
{
    public function __construct(
        private int $count,
    ) {
    }

    public function next(): ?Pong
    {
        if (0 === $this->count) {
            return null;
        }

        return new Pong($this->count - 1);
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
