<?php

namespace Joschi127\DoctrineEntityOverrideBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class Joschi127DoctrineEntityOverrideExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $overriddenEntities = [];
        foreach ($configs as $config){
            if (isset($config['overridden_entities'])) {
                $overriddenEntities = array_merge($overriddenEntities, $config['overridden_entities']);
            }
        }
        
        $container->setParameter($this->getAlias() . '.config.overridden_entities', $overriddenEntities);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
