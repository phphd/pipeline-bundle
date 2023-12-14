<?php

declare(strict_types=1);

namespace PhPhD\Pipeline\Messenger;

use PhPhD\Pipeline\PipeForward;
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

final class ForwardingMiddleware implements MiddlewareInterface
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

        $forwardMessage = end($handledStamps)->getResult();

        if (!is_object($forwardMessage)) {
            return $handledEnvelope;
        }

        if (!$this->isForwardable($forwardMessage)) {
            return $handledEnvelope;
        }

        $forwardEnvelope = $this->wrapForwardEnvelope($handledEnvelope, $forwardMessage);

        $resultEnvelope = $this->handle($forwardEnvelope, $forwardStack);

        return $this->prependHandledStamps($resultEnvelope, $handledStamps);
    }

    private function wrapForwardEnvelope(Envelope $handledEnvelope, object $forwardMessage): Envelope
    {
        return Envelope::wrap($forwardMessage, getStampsAsFlatList($handledEnvelope))
            ->withoutAll(HandledStamp::class)
        ;
    }

    private function isForwardable(object $message): bool
    {
        $reflectionClass = new ReflectionClass($message);

        $attributes = $reflectionClass->getAttributes(PipeForward::class);

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
