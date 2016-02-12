<?php

/*
 * This file is based on the code of the Sylius ResourceBundle.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Joschi127\DoctrineEntityOverrideBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Resolves given target entities with container parameters.
 * Usable only with *doctrine/orm* driver.
 *
 * Based on the code of the Sylius ResourceBundle.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@sylius.pl>
 */
class DoctrineTargetEntitiesResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
            throw new \RuntimeException('Cannot find Doctrine RTEL');
        }

        $overriddenEntities = $container->getParameter('joschi127_doctrine_entity_override.config.overridden_entities');
        $resolveTargetEntityListener = $container->findDefinition('doctrine.orm.listeners.resolve_target_entity');

        foreach ($overriddenEntities as $interface => $class) {
            $interface = $this->getInterface($container, $interface);
            $class = $this->getClass($container, $class);

            $resolveTargetEntityListener
                ->addMethodCall('addResolveTargetEntity', array(
                    $interface,
                    $class,
                    array()
                ))
            ;
        }

        if (!$resolveTargetEntityListener->hasTag('doctrine.event_listener')) {
            $resolveTargetEntityListener->addTag('doctrine.event_listener', array('event' => 'loadClassMetadata'));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $key
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getInterface(ContainerBuilder $container, $key)
    {
        if ($container->hasParameter($key)) {
            return $container->getParameter($key);
        }

        if (interface_exists($key) || class_exists($key)) {
            return $key;
        }

        throw new \InvalidArgumentException(
            sprintf('The interface or class %s does not exists.', $key)
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $key
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getClass(ContainerBuilder $container, $key)
    {
        if ($container->hasParameter($key)) {
            return $container->getParameter($key);
        }

        if (class_exists($key)) {
            return $key;
        }

        throw new \InvalidArgumentException(
            sprintf('The class %s does not exists.', $key)
        );
    }
}
