<?php

/*
 * This file is based on the code of the Sylius ResourceBundle.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Joschi127\DoctrineEntityOverrideBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Doctrine listener used to manipulate mappings.
 *
 * Based on the code of the Sylius ResourceBundle.
 *
 * @author Ivan Molchanov <ivan.molchanov@opensoftdev.ru>
 */
class LoadORMMetadataSubscriber implements EventSubscriber
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $overriddenEntities;

    /**
     * Constructor
     *
     * @param array $overriddenEntities
     */
    public function __construct(ContainerInterface $container, array $overriddenEntities)
    {
        $this->container = $container;
        $this->overriddenEntities = $overriddenEntities;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata'
        );
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        $this->setIsMappedSuperclass($metadata);
        $this->setCustomRepositoryClasses($metadata, $eventArgs->getEntityManager()->getConfiguration());

        if (!$metadata->isMappedSuperclass) {
            $this->setAssociationMappings($metadata, $eventArgs->getEntityManager()->getConfiguration());
        } else {
            $this->unsetAssociationMappings($metadata);
        }
    }

    private function setIsMappedSuperclass(ClassMetadataInfo $metadata)
    {
        foreach ($this->overriddenEntities as $interface => $class) {
            $interface = $this->getInterface($interface);
            $class = $this->getClass($class);
            if ($class === $metadata->getName()) {
                $metadata->isMappedSuperclass = false;
            }
            if ($interface === $metadata->getName()) {
                $metadata->isMappedSuperclass = true;
            }
        }
    }


    private function setCustomRepositoryClasses(ClassMetadataInfo $metadata, $configuration)
    {
        if ($metadata->customRepositoryClassName) {
            return;
        }

        foreach ($this->overriddenEntities as $interface => $class) {
            $class = $this->getClass($class);

            if ($class === $metadata->getName()) {
                foreach (class_parents($metadata->getName()) as $parent) {
                    $parentMetadata = new ClassMetadata(
                        $parent,
                        $configuration->getNamingStrategy()
                    );
                    if ($parentMetadata->customRepositoryClassName) {
                        $metadata->setCustomRepositoryClass($parentMetadata->customRepositoryClassName);
                    }
                }
            }
        }
    }

    private function setAssociationMappings(ClassMetadataInfo $metadata, $configuration)
    {
        foreach ($this->overriddenEntities as $interface => $class) {
            $class = $this->getClass($class);

            if ($class === $metadata->getName()) {
                foreach (class_parents($metadata->getName()) as $parent) {
                    $parentMetadata = new ClassMetadata(
                        $parent,
                        $configuration->getNamingStrategy()
                    );
                    if (in_array($parent, $configuration->getMetadataDriverImpl()->getAllClassNames())) {
                        $configuration->getMetadataDriverImpl()->loadMetadataForClass($parent, $parentMetadata);
                        foreach ($parentMetadata->getAssociationMappings() as $key => $value) {
                            if ($this->typeIsRelation($value['type'])) {
                                $metadata->associationMappings[$key] = $value;
                            }
                        }
                    }
                }
            }
        }
    }

    private function unsetAssociationMappings(ClassMetadataInfo $metadata)
    {
        foreach ($metadata->getAssociationMappings() as $key => $value) {
            if ($this->typeIsRelation($value['type'])) {
                unset($metadata->associationMappings[$key]);
            }
        }
    }

    private function typeIsRelation($type)
    {
        return in_array(
            $type,
            array(
                ClassMetadataInfo::MANY_TO_MANY,
                ClassMetadataInfo::ONE_TO_MANY,
                ClassMetadataInfo::ONE_TO_ONE
            ),
            true
        );
    }

    /**
     * @param string           $key
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getInterface($key)
    {
        if ($this->container->hasParameter($key)) {
            return $this->container->getParameter($key);
        }

        if (interface_exists($key) || class_exists($key)) {
            return $key;
        }

        throw new \InvalidArgumentException(
            sprintf('The interface or class %s does not exists.', $key)
        );
    }

    /**
     * @param string           $key
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getClass($key)
    {
        if ($this->container->hasParameter($key)) {
            return $this->container->getParameter($key);
        }

        if (class_exists($key)) {
            return $key;
        }

        throw new \InvalidArgumentException(
            sprintf('The class %s does not exists.', $key)
        );
    }
}
