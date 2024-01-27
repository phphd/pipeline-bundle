<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle\DependencyInjection;

use PhPhD\PipelineBundle\Messenger\ForwardChainMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class PhdPipelineExtension extends Extension
{
    public const ALIAS = 'phd_pipeline';

    /**
     * @param array<array-key,mixed> $configs
     *
     * @override
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->register('phd_pipeline.forward_chain', ForwardChainMiddleware::class);

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->getDefinition('phd_pipeline.forward_chain')->setPublic(true);
        }
    }

    /** @override */
    public function getAlias(): string
    {
        return self::ALIAS;
    }
}
