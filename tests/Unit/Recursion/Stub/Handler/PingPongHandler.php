<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Handler;

use PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Message\Ping;
use PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Message\Pong;

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
