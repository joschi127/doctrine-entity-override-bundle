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
     * @var array
     */
    protected $parentClassesByClass = [];

    /**
     * Constructor
     *
     * @param array $overriddenEntities
     */
    public function __construct(ContainerInterface $container, array $overriddenEntities)
    {
        $this->container = $container;
        $this->overriddenEntities = $overriddenEntities;
        foreach ($overriddenEntities as $interface => $class) {
            $class = $this->getClass($class);
            $this->parentClassesByClass[$class] = array_values(class_parents($class));
        }
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

        if (!$metadata->isMappedSuperclass) {
            $this->setCustomRepositoryClasses($metadata, $eventArgs->getEntityManager()->getConfiguration());
            $this->setAssociationMappings($metadata, $eventArgs->getEntityManager()->getConfiguration());
        } else {
            $this->unsetAssociationMappings($metadata);
            $this->unsetDuplicateFieldMappings($metadata, $eventArgs->getEntityManager()->getConfiguration());
        }
    }

    protected function setIsMappedSuperclass(ClassMetadataInfo $metadata)
    {
        foreach ($this->overriddenEntities as $interface => $class) {
            $interface = $this->getInterface($interface);
            $class = $this->getClass($class);

            // set isMappedSuperclass = false for the actually used class
            if ($class === $metadata->getName()) {
                $metadata->isMappedSuperclass = false;

                return;
            }

            // set isMappedSuperclass = true for the super class / interface which is overridden
            if ($interface === $metadata->getName()) {
                $metadata->isMappedSuperclass = true;

                return;
            }

            // set isMappedSuperclass = true for all other parent classes of the actually used class to allow
            // overriding with multiple levels of inheritance
            foreach($this->parentClassesByClass[$class] as $parentClass) {
                if ($parentClass === $metadata->getName()) {
                    $metadata->isMappedSuperclass = true;

                    return;
                }
            }
        }
    }


    protected function setCustomRepositoryClasses(ClassMetadataInfo $metadata, $configuration)
    {
        if ($metadata->customRepositoryClassName) {
            return;
        }

        foreach ($this->overriddenEntities as $interface => $class) {
            $class = $this->getClass($class);

            if ($class === $metadata->getName()) {
                // inherit custom repository class of first parent classes which has one for the actually used class
                foreach ($this->parentClassesByClass[$class] as $parentClass) {
                    $parentMetadata = new ClassMetadata(
                        $parentClass,
                        $configuration->getNamingStrategy()
                    );
                    if ($parentMetadata->customRepositoryClassName) {
                        $metadata->setCustomRepositoryClass($parentMetadata->customRepositoryClassName);

                        return;
                    }
                }
            }
        }
    }

    protected function setAssociationMappings(ClassMetadataInfo $metadata, $configuration)
    {
        foreach ($this->overriddenEntities as $interface => $class) {
            $class = $this->getClass($class);

            if ($class === $metadata->getName()) {
                // inherit association mappings of all parent classes for the actually used class
                foreach ($this->parentClassesByClass[$class] as $parentClass) {
                    $parentMetadata = new ClassMetadata(
                        $parentClass,
                        $configuration->getNamingStrategy()
                    );
                    if (in_array($parentClass, $configuration->getMetadataDriverImpl()->getAllClassNames())) {
                        $configuration->getMetadataDriverImpl()->loadMetadataForClass($parentClass, $parentMetadata);
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

    protected function unsetAssociationMappings(ClassMetadataInfo $metadata)
    {
        if ($this->classIsOverridden($metadata->getName())) {
            // remove all association mappings from mapped super classes, which are not allowed to have association mappings
            foreach ($metadata->getAssociationMappings() as $key => $value) {
                if ($this->typeIsRelation($value['type'])) {
                    unset($metadata->associationMappings[$key]);
                }
            }
        }
    }

    protected function unsetDuplicateFieldMappings(ClassMetadataInfo $metadata, $configuration)
    {
        // if class is overridden and class is not the interface itself ...
        // (class is "in between" in a multi level inheritance)
        if ($this->classIsOverridden($metadata->getName()) && !isset($this->overriddenEntities[$metadata->getName()])) {
            // ... unset all mapped fields (not sure why, but this is required, otherwise a MappingException "Duplicate
            // definition of column" will be thrown when loading the metadata of the actually used class)
            foreach ($metadata->fieldMappings as $key => $value) {
                if (!isset($value['declared']) || $value['declared'] === $metadata->getName()) {
                    unset($metadata->fieldMappings[$key]);
                }
            }
        }

        return;
    }

    protected function classIsOverridden($className)
    {
        foreach ($this->overriddenEntities as $interface => $class) {
            $interface = $this->getInterface($interface);
            $class = $this->getClass($class);

            if ($interface === $className) {
                return true;
            }

            foreach($this->parentClassesByClass[$class] as $parentClass) {
                if ($parentClass === $className) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function typeIsRelation($type)
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
    protected function getInterface($key)
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
    protected function getClass($key)
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
