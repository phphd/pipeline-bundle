<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Unit\Extended;

use PhPhD\Pipeline\NextForwarded;
use PhPhD\PipelineBundle\Messenger\ForwardChainMiddleware;
use PhPhD\PipelineBundle\Tests\Integration\TestCase;
use PhPhD\PipelineBundle\Tests\Unit\Basic\Stub\Message\OriginalMessage;
use stdClass;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @covers \PhPhD\Pipeline\NextForwarded
 * @covers \PhPhD\PipelineBundle\Messenger\ForwardChainMiddleware
 * @covers \PhPhD\PipelineBundle\Messenger\getStampsAsFlatList
 *
 * @internal
 */
final class ExtendedForwardingTest extends TestCase
{
    private MessageBus $messageBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageBus = new MessageBus([
            new ForwardChainMiddleware(),
            new HandleMessageMiddleware(new HandlersLocator([
                OriginalMessage::class => [
                    static fn (): NextForwarded => new NextForwarded(new stdClass()),
                ],
                stdClass::class => [
                    static fn (): string => 'the result',
                ],
            ]), true),
        ]);
    }

    public function testResultIsForwardedExplicitly(): void
    {
        $envelope = $this->messageBus->dispatch(new OriginalMessage());

        /** @var HandledStamp $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);

        self::assertSame('the result', $handledStamp->getResult());
    }
}
