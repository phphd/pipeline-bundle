<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Messenger;

use PhPhD\Pipeline\NextForwarded;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

use function array_merge;
use function array_values;
use function end;
use function is_object;

final class ForwardChainMiddleware implements MiddlewareInterface
{
    /** @throws ReflectionException */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $forwardStack = clone $stack;

        $handledEnvelope = $stack->next()->handle($envelope, $stack);

        /** @var list<HandledStamp> $handledStamps */
        $handledStamps = $handledEnvelope->all(HandledStamp::class);

        if ([] === $handledStamps) {
            return $handledEnvelope;
        }

        $nextMessage = end($handledStamps)->getResult();

        if (!is_object($nextMessage)) {
            return $handledEnvelope;
        }

        if (!$this->shouldBeForwarded($nextMessage)) {
            return $handledEnvelope;
        }

        $forwardEnvelope = $this->wrapForwardEnvelope($handledEnvelope, $nextMessage);

        $resultEnvelope = $this->handle($forwardEnvelope, $forwardStack);

        return $this->prependHandledStamps($resultEnvelope, $handledStamps);
    }

    private function wrapForwardEnvelope(Envelope $handledEnvelope, object $forwardMessage): Envelope
    {
        return Envelope::wrap($forwardMessage, getStampsAsFlatList($handledEnvelope))
            ->withoutAll(HandledStamp::class)
        ;
    }

    private function shouldBeForwarded(object $nextMessage): bool
    {
        $reflectionClass = new ReflectionClass($nextMessage);

        $attributes = $reflectionClass->getAttributes(NextForwarded::class);

        return [] !== $attributes;
    }

    /** @param list<HandledStamp> $handledStamps */
    private function prependHandledStamps(Envelope $envelope, array $handledStamps): Envelope
    {
        /** @var list<HandledStamp> $latestHandledStamps */
        $latestHandledStamps = $envelope->all(HandledStamp::class);

        $resultHandledStamps = [...$handledStamps, ...$latestHandledStamps];

        return $envelope
            ->withoutAll(HandledStamp::class)
            ->with(...$resultHandledStamps)
        ;
    }
}

/**
 * @internal
 *
 * @return list<StampInterface>
 */
function getStampsAsFlatList(Envelope $envelope): array
{
    /** @var list<StampInterface>[] $stamps */
    $stamps = $envelope->all();

    return array_merge(...array_values($stamps));
}
