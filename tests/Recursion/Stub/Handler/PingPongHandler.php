<?php

declare(strict_types=1);

namespace PhPhD\Pipeline\Tests\Recursion\Stub\Handler;

use PhPhD\Pipeline\Tests\Recursion\Stub\Message\Ping;
use PhPhD\Pipeline\Tests\Recursion\Stub\Message\Pong;

final class PingPongHandler
{
    public function __invoke(Ping|Pong $message): Ping|Pong|string
    {
        $nextMessage = $message->next();

        if (null === $nextMessage) {
            return 'completed!';
        }

        return $nextMessage;
    }
}
