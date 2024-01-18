<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Integration;

use PhPhD\PipelineBundle\Messenger\ForwardingMiddleware;

/**
 * @covers \PhPhD\PipelineBundle\PhdPipelineBundle
 * @covers \PhPhD\PipelineBundle\DependencyInjection\PhdPipelineExtension
 *
 * @internal
 */
final class DependencyInjectionTest extends TestCase
{
    public function testRegistersMiddlewareService(): void
    {
        $container = self::getContainer();

        $forwardingMiddleware = $container->get('phd_pipeline.forwarding');

        self::assertInstanceOf(ForwardingMiddleware::class, $forwardingMiddleware);
    }
}
