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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\ORMException;
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
        $em = $eventArgs->getEntityManager();

        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        $wasMappedSuperclass = $metadata->isMappedSuperclass;
        $this->setIsMappedSuperclass($metadata);

        if (!$metadata->isMappedSuperclass) {
            $this->setCustomRepositoryClasses($metadata, $eventArgs->getEntityManager()->getConfiguration());
            $this->setAssociationMappings($metadata, $eventArgs->getEntityManager()->getConfiguration());
            $this->setFieldMappings($metadata, $eventArgs->getEntityManager()->getConfiguration(), $em);
        } else {
            $this->unsetAssociationMappings($metadata);
            $this->unsetFieldMappings($metadata, $wasMappedSuperclass);
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

                break;
            }

            // set isMappedSuperclass = true for the super class / interface which is overridden
            if ($interface === $metadata->getName()) {
                $metadata->isMappedSuperclass = true;

                break;
            }

            // set isMappedSuperclass = true for all other parent classes of the actually used class to allow
            // overriding with multiple levels of inheritance
            foreach($this->parentClassesByClass[$class] as $parentClass) {
                if ($parentClass === $metadata->getName()) {
                    $metadata->isMappedSuperclass = true;

                    break 2;
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
                    $parentMetadata = $this->getClassMetadata($parentClass, $configuration);

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
                    $parentMetadata = $this->getClassMetadata($parentClass, $configuration);

                    if (in_array($parentClass, $configuration->getMetadataDriverImpl()->getAllClassNames())) {
                        $configuration->getMetadataDriverImpl()->loadMetadataForClass($parentClass, $parentMetadata);

                        if ($this->classIsOverridden($parentClass)) {
                            foreach ($parentMetadata->getAssociationMappings() as $name => $mapping) {
                                //if ($this->typeIsRelation($mapping['type'])) {
                                    // update sourceEntity of association mapping
                                    if (isset($mapping['sourceEntity']) && $mapping['sourceEntity'] == $parentClass) {
                                        $mapping['sourceEntity'] = $class;
                                    }

                                    // add association mapping for actually used class
                                    $metadata->associationMappings[$name] = $mapping;
                                //}
                            }
                        }
                    }
                }
            }
        }
    }

    protected function setFieldMappings(ClassMetadataInfo $metadata, $configuration, EntityManager $em)
    {
        foreach ($this->overriddenEntities as $interface => $class) {
            $class = $this->getClass($class);

            if ($class === $metadata->getName()) {
                // inherit association mappings of all parent classes for the actually used class
                foreach ($this->parentClassesByClass[$class] as $parentClass) {
                    $parentMetadata = $this->getClassMetadata($parentClass, $configuration);
                    $parentWasMappedSuperclass = $parentMetadata->isMappedSuperclass;

                    if (in_array($parentClass, $configuration->getMetadataDriverImpl()->getAllClassNames())) {
                        $configuration->getMetadataDriverImpl()->loadMetadataForClass($parentClass, $parentMetadata);

                        // if parent class is overridden and class is not the interface itself ...
                        // (parent class is "in between" in a multi level inheritance)
                        if ($this->classIsOverridden($parentClass) && !isset($this->overriddenEntities[$parentClass])) {
                            // ... add field mapping of the parent class to the the actually used class - it was removed
                            // from the parent class in unsetFieldMappings()
                            foreach ($parentMetadata->fieldMappings as $name => $mapping) {
                                // remove existing mapping for these fields
                                unset($metadata->fieldMappings[$mapping['fieldName']]);
                                unset($metadata->columnNames[$mapping['fieldName']]);
                                unset($metadata->fieldNames[$mapping['columnName']]);

                                // re-add mapping for these fields (as if it were fields of the class itself, not
                                // inherited fields)
                                unset($mapping['declared']);
                                unset($mapping['inherited']);
                                $metadata->mapField($mapping);
                            }
                            foreach ($parentMetadata->reflFields as $name => $field) {
                                if (!isset($metadata->reflFields[$name])) {
                                    $metadata->reflFields[$name] = $field;
                                }
                            }
                        }

                        // if parent class is overridden and class is the interface itself, but was originally not
                        // defined as mapped superclass ... (class was set to isMappedSuperclass = true by this
                        // listener)
                        else if ($this->classIsOverridden($parentClass) && !$parentWasMappedSuperclass) {
                            // ... re-add field mapping of the parent class to the the actually used class - the fields
                            // were set to declared / inherited in unsetFieldMappings(), but this was to late, after
                            // loading the metadata, so to correctly apply the changes, we have to re-add the mapping
                            // here
                            foreach ($metadata->fieldMappings as $name => $mapping) {
                                if (isset($mapping['declared']) && $mapping['declared'] == $parentClass) {
                                    // remove existing mapping for these fields
                                    unset($metadata->fieldMappings[$mapping['fieldName']]);
                                    unset($metadata->columnNames[$mapping['fieldName']]);
                                    unset($metadata->fieldNames[$mapping['columnName']]);

                                    // re-add mapping for these fields (as if it were fields of the class itself, not
                                    // inherited fields)
                                    // keep declared flag and unset inherited flag!
                                    unset($mapping['inherited']);
                                    $metadata->mapField($mapping);
                                }
                            }
                            foreach ($parentMetadata->reflFields as $name => $field) {
                                if (!isset($metadata->reflFields[$name])) {
                                    $metadata->reflFields[$name] = $field;
                                }
                            }
                            if ($parentMetadata->generatorType != ClassMetadata::GENERATOR_TYPE_NONE
                                && $metadata->generatorType == ClassMetadata::GENERATOR_TYPE_NONE)
                            {
                                $metadata->setIdGeneratorType($parentMetadata->generatorType);
                                $this->completeIdGeneratorMapping($metadata, $em);
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
            foreach ($metadata->getAssociationMappings() as $name => $mapping) {
                //if ($this->typeIsRelation($mapping['type'])) {
                    unset($metadata->associationMappings[$name]);
                //}
            }
        }
    }

    protected function unsetFieldMappings(ClassMetadataInfo $metadata, $wasMappedSuperclass)
    {
        // if class is overridden and class is not the interface itself ...
        // (class is "in between" in a multi level inheritance)
        if ($this->classIsOverridden($metadata->getName()) && !isset($this->overriddenEntities[$metadata->getName()])) {
            // ... unset all mapped fields (otherwise a MappingException "Duplicate definition of column" will be thrown
            // when loading the metadata of the actually used class)
            // it will later be added as mapping for the actually used class in setFieldMappings()
            foreach ($metadata->fieldMappings as $name => $mapping) {
                if (!isset($mapping['declared']) || $mapping['declared'] === $metadata->getName()) {
                    unset($metadata->fieldMappings[$mapping['fieldName']]);
                    unset($metadata->columnNames[$mapping['fieldName']]);
                    unset($metadata->fieldNames[$mapping['columnName']]);
                }
            }
        }

        // if class is overridden and class is the interface itself, but was originally not defined as mapped
        // superclass ... (class was set to isMappedSuperclass = true by this listener)
        else if ($this->classIsOverridden($metadata->getName()) && !$wasMappedSuperclass) {

            // ... set fields to declared / inherited to avoid MappingException "Duplicate definition of column" when
            // loading the metadata of sub classes (this only happens if this class originally was defined as entity but
            // changed to a mapped superclass by this listener - it is actually a "hack" to fix it like that but we did
            // not find a better way to get this working)
            // to make this work correctly, these fields will later be re-added in setFieldMappings() to the actual
            // class (but we will keep the declared / inherited flags this time)
            foreach ($metadata->fieldMappings as $name => $mapping) {
                if (!isset($mapping['declared'])) { // only if not already set
                    if (!$metadata->getReflectionClass()->getProperty($name)->isPrivate()) {
                        $metadata->fieldMappings[$mapping['fieldName']]['declared'] = $metadata->getName();
                        $metadata->fieldMappings[$mapping['fieldName']]['inherited'] = $metadata->getName();
                    }
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

    protected function getClassMetadata($class, $configuration)
    {
        $metadata = new ClassMetadata(
            $class,
            $configuration->getNamingStrategy()
        );

        return $metadata;
    }

    /**
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate for the targeted database platform.
     *
     * Most of the code in this methos is a copy of the code from
     * vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/ClassMetadataFactory.php.
     *
     * @return void
     *
     * @throws ORMException
     */
    protected function completeIdGeneratorMapping(ClassMetadataInfo $class, EntityManager $em)
    {
        $idGenType = $class->generatorType;
        if ($idGenType == ClassMetadata::GENERATOR_TYPE_AUTO) {
            if ($em->getConnection()->getDatabasePlatform()->prefersSequences()) {
                $class->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_SEQUENCE);
            } else if ($em->getConnection()->getDatabasePlatform()->prefersIdentityColumns()) {
                $class->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
            } else {
                $class->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_TABLE);
            }
        }

        // Create & assign an appropriate ID generator instance
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_IDENTITY:
                // For PostgreSQL IDENTITY (SERIAL) we need a sequence name. It defaults to
                // <table>_<column>_seq in PostgreSQL for SERIAL columns.
                // Not pretty but necessary and the simplest solution that currently works.
                $sequenceName = null;
                $fieldName    = $class->identifier ? $class->getSingleIdentifierFieldName() : null;

                if ($em->getConnection()->getDatabasePlatform() instanceof Platforms\PostgreSQLPlatform) {
                    $columnName     = $class->getSingleIdentifierColumnName();
                    $quoted         = isset($class->fieldMappings[$fieldName]['quoted']) || isset($class->table['quoted']);
                    $sequenceName   = $class->getTableName() . '_' . $columnName . '_seq';
                    $definition     = array(
                        'sequenceName' => $em->getConnection()->getDatabasePlatform()->fixSchemaElementName($sequenceName)
                    );

                    if ($quoted) {
                        $definition['quoted'] = true;
                    }

                    $sequenceName = $em
                        ->getConfiguration()
                        ->getQuoteStrategy()
                        ->getSequenceName($definition, $class, $em->getConnection()->getDatabasePlatform());
                }

                $generator = ($fieldName && $class->fieldMappings[$fieldName]['type'] === 'bigint')
                    ? new BigIntegerIdentityGenerator($sequenceName)
                    : new IdentityGenerator($sequenceName);

                $class->setIdGenerator($generator);

                break;

            case ClassMetadata::GENERATOR_TYPE_SEQUENCE:
                // If there is no sequence definition yet, create a default definition
                $definition = $class->sequenceGeneratorDefinition;

                if ( ! $definition) {
                    $fieldName      = $class->getSingleIdentifierFieldName();
                    $columnName     = $class->getSingleIdentifierColumnName();
                    $quoted         = isset($class->fieldMappings[$fieldName]['quoted']) || isset($class->table['quoted']);
                    $sequenceName   = $class->getTableName() . '_' . $columnName . '_seq';
                    $definition     = array(
                        'sequenceName'      => $em->getConnection()->getDatabasePlatform()->fixSchemaElementName($sequenceName),
                        'allocationSize'    => 1,
                        'initialValue'      => 1,
                    );

                    if ($quoted) {
                        $definition['quoted'] = true;
                    }

                    $class->setSequenceGeneratorDefinition($definition);
                }

                $sequenceGenerator = new \Doctrine\ORM\Id\SequenceGenerator(
                    $em->getConfiguration()->getQuoteStrategy()->getSequenceName($definition, $class, $em->getConnection()->getDatabasePlatform()),
                    $definition['allocationSize']
                );
                $class->setIdGenerator($sequenceGenerator);
                break;

            case ClassMetadata::GENERATOR_TYPE_NONE:
                $class->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
                break;

            case ClassMetadata::GENERATOR_TYPE_UUID:
                $class->setIdGenerator(new \Doctrine\ORM\Id\UuidGenerator());
                break;

            case ClassMetadata::GENERATOR_TYPE_TABLE:
                throw new ORMException("TableGenerator not yet implemented.");
                break;

            case ClassMetadata::GENERATOR_TYPE_CUSTOM:
                $definition = $class->customGeneratorDefinition;
                if ( ! class_exists($definition['class'])) {
                    throw new ORMException("Can't instantiate custom generator : " .
                        $definition['class']);
                }
                $class->setIdGenerator(new $definition['class']);
                break;

            default:
                throw new ORMException("Unknown generator type: " . $class->generatorType);
        }
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
