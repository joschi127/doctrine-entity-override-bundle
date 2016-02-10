<?php

/*
 * This file is based on the code of the Sylius ResourceBundle.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Joschi127\DoctrineEntityOverrideBundle\DependencyInjection\Compiler;

use Joschi127\DoctrineEntityOverrideBundle\DependencyInjection\DoctrineTargetEntitiesResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Resolves given target entities with container parameters.
 * Usable only with *doctrine/orm* driver.
 *
 * Based on the code of the Sylius ResourceBundle.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@sylius.pl>
 */
class ResolveDoctrineTargetEntitiesPass implements CompilerPassInterface
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resolver = new DoctrineTargetEntitiesResolver();
        $resolver->resolve($container);
    }
}
