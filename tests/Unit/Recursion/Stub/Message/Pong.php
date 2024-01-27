<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Message;

use PhPhD\Pipeline\NextForwarded;

#[NextForwarded]
final class Pong
{
    public function __construct(
        private int $count,
    ) {
    }

    public function next(): ?Ping
    {
        if (0 === $this->count) {
            return null;
        }

        return new Ping($this->count - 1);
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
