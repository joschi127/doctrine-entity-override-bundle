<?php

namespace Joschi127\DoctrineEntityOverrideBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Joschi127\DoctrineEntityOverrideBundle\DependencyInjection\Compiler\ResolveDoctrineTargetEntitiesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Joschi127DoctrineEntityOverrideBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ResolveDoctrineTargetEntitiesPass());
    }
}