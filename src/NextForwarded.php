<?php

declare(strict_types=1);

namespace PhPhD\Pipeline;

use Attribute;

/** @template T of object|null */
#[Attribute(Attribute::TARGET_CLASS)]
final class NextForwarded
{
    public function __construct(
        /** @var T */
        private ?object $message = null,
    ) {
    }

    /** @return T */
    public function getMessage(): ?object
    {
        return $this->message;
    }
}
