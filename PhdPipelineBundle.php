<?php

declare(strict_types=1);

namespace PhPhD\PipelineBundle;

use PhPhD\PipelineBundle\DependencyInjection\PhdPipelineExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/** @api */
final class PhdPipelineBundle extends Bundle
{
    /** @override */
    protected function createContainerExtension(): PhdPipelineExtension
    {
        return new PhdPipelineExtension();
    }
}
