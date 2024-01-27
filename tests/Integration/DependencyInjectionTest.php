<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\Tests\Integration;

use PhPhD\PipelineBundle\Messenger\ForwardChainMiddleware;

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

        $forwardingMiddleware = $container->get('phd_pipeline.forward_chain');

        self::assertInstanceOf(ForwardChainMiddleware::class, $forwardingMiddleware);
    }
}
