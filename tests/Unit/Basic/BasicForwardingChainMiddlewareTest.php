<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Unit\Basic;

use PhPhD\PipelineBundle\Messenger\ForwardChainMiddleware;
use PhPhD\PipelineBundle\Tests\Unit\Basic\Stub\Message\FirstForwardedMessage;
use PhPhD\PipelineBundle\Tests\Unit\Basic\Stub\Message\NoopMessage;
use PhPhD\PipelineBundle\Tests\Unit\Basic\Stub\Message\OriginalMessage;
use PhPhD\PipelineBundle\Tests\Unit\Basic\Stub\Message\ScalarResultMessage;
use PhPhD\PipelineBundle\Tests\Unit\Basic\Stub\Message\SecondForwardedMessage;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;

use function PhPhD\PipelineBundle\Messenger\getStampsAsFlatList;

/**
 * @internal
 *
 * @covers \PhPhD\Pipeline\NextForwarded
 * @covers \PhPhD\PipelineBundle\Messenger\ForwardChainMiddleware
 * @covers \PhPhD\PipelineBundle\Messenger\getStampsAsFlatList
 */
final class BasicForwardingChainMiddlewareTest extends TestCase
{
    private MessageBus $messageBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageBus = new MessageBus([
            new ForwardChainMiddleware(),
            new HandleMessageMiddleware(new HandlersLocator([
                OriginalMessage::class => [
                    static fn (): FirstForwardedMessage => new FirstForwardedMessage(),
                ],
                FirstForwardedMessage::class => [
                    static fn (): SecondForwardedMessage => new SecondForwardedMessage(),
                ],
                SecondForwardedMessage::class => [
                    static fn (): string => 'final result',
                ],
                ScalarResultMessage::class => [
                    static fn (): int => 1335,
                ],
                stdClass::class => [
                    static fn (): OriginalMessage => new OriginalMessage(),
                ],
            ]), true),
        ]);
    }

    public function testForwardsMessagesPreservingHandledStamps(): void
    {
        $envelope = $this->messageBus->dispatch(new OriginalMessage());

        /** @var HandledStamp[] $handledStamps */
        $handledStamps = getStampsAsFlatList($envelope);

        self::assertContainsOnlyInstancesOf(HandledStamp::class, $handledStamps);
        self::assertCount(3, $handledStamps);

        [$firstHandled, $secondHandled, $thirdHandled] = $handledStamps;

        self::assertInstanceOf(FirstForwardedMessage::class, $firstHandled->getResult());
        self::assertSame('Closure', $firstHandled->getHandlerName());

        self::assertInstanceOf(SecondForwardedMessage::class, $secondHandled->getResult());
        self::assertSame('Closure', $secondHandled->getHandlerName());

        self::assertSame('final result', $thirdHandled->getResult());
        self::assertSame('Closure', $thirdHandled->getHandlerName());
    }

    public function testAllowsNoHandlers(): void
    {
        $envelope = $this->messageBus->dispatch(new NoopMessage());

        self::assertSame([], $envelope->all());
    }

    public function testForwardAttributeIsIgnoredWhenHandlerReturnsScalarResult(): void
    {
        $envelope = $this->messageBus->dispatch(new ScalarResultMessage());

        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        self::assertCount(1, $handledStamps);

        [$handledStamp] = $handledStamps;

        self::assertSame(1335, $handledStamp->getResult());
    }

    public function testDoesNotForwardMessageWithoutAttribute(): void
    {
        $envelope = $this->messageBus->dispatch(new stdClass());

        /** @var HandledStamp[] $handledStamps */
        $handledStamps = getStampsAsFlatList($envelope);

        self::assertContainsOnlyInstancesOf(HandledStamp::class, $handledStamps);
        self::assertCount(1, $handledStamps);

        [$handledStamp] = $handledStamps;

        self::assertInstanceOf(OriginalMessage::class, $handledStamp->getResult());
    }
}
