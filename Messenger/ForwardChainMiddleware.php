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

        $result = end($handledStamps)->getResult();

        if (!is_object($result)) {
            return $handledEnvelope;
        }

        $nextMessage = $this->getNextMessage($result);

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

    private function getNextMessage(object $result): ?object
    {
        if ($result instanceof NextForwarded) {
            return $result->getMessage();
        }

        if ($this->hasForwardingAttribute($result)) {
            return $result;
        }

        return null;
    }

    private function hasForwardingAttribute(object $message): bool
    {
        $reflectionClass = new ReflectionClass($message);

        $attributes = $reflectionClass->getAttributes(NextForwarded::class);

        return [] !== $attributes;
    }

    /** @psalm-assert-if-true !null $nextMessage */
    private function shouldBeForwarded(?object $nextMessage): bool
    {
        return null !== $nextMessage;
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
