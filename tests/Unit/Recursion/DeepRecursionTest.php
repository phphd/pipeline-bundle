<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Unit\Recursion;

use PhPhD\PipelineBundle\Messenger\ForwardingMiddleware;
use PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Handler\PingPongHandler;
use PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Message\Ping;
use PhPhD\PipelineBundle\Tests\Unit\Recursion\Stub\Message\Pong;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;

use function array_map;
use function PhPhD\PipelineBundle\Messenger\getStampsAsFlatList;

/**
 * @internal
 *
 * @covers \PhPhD\Pipeline\PipeForward
 * @covers \PhPhD\PipelineBundle\Messenger\ForwardingMiddleware
 * @covers \PhPhD\PipelineBundle\Messenger\getStampsAsFlatList
 */
final class DeepRecursionTest extends TestCase
{
    private MessageBus $messageBus;

    protected function setUp(): void
    {
        parent::setUp();

        $pingPongHandler = new PingPongHandler();

        $this->messageBus = new MessageBus([
            new ForwardingMiddleware(),
            new HandleMessageMiddleware(new HandlersLocator([
                Ping::class => [$pingPongHandler],
                Pong::class => [$pingPongHandler],
            ]), true),
        ]);
    }

    public function testHandlesDeepRecursion(): void
    {
        $envelope = $this->messageBus->dispatch(new Ping(3));

        /** @var HandledStamp[] $handledStamps */
        $handledStamps = getStampsAsFlatList($envelope);

        self::assertContainsOnlyInstancesOf(HandledStamp::class, $handledStamps);
        self::assertCount(4, $handledStamps);

        array_map(
            static fn (HandledStamp $stamp) => self::assertSame(
                PingPongHandler::class.'::__invoke',
                $stamp->getHandlerName(),
            ),
            $handledStamps,
        );

        /** @var non-empty-list<Ping|Pong|string> $results */
        $results = array_map(static fn (HandledStamp $stamp): mixed => $stamp->getResult(), $handledStamps);

        [$pong2, $ping1, $pong0, $completed] = $results;

        self::assertInstanceOf(Pong::class, $pong2);
        self::assertSame(2, $pong2->getCount());

        self::assertInstanceOf(Ping::class, $ping1);
        self::assertSame(1, $ping1->getCount());

        self::assertInstanceOf(Pong::class, $pong0);
        self::assertSame(0, $pong0->getCount());

        self::assertSame('completed!', $completed);
    }
}
