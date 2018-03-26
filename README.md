doctrine-entity-override-bundle
===============================

[![Build Status](https://travis-ci.org/joschi127/doctrine-entity-override-bundle.svg?branch=master)](https://travis-ci.org/joschi127/doctrine-entity-override-bundle)

Symfony bundle which allows to override entities by using inheritance

Configuration
-------------

Add to `app/config/config.yml`:

    # override entities
    joschi127_doctrine_entity_override:
        overridden_entities:
            # Keep in mind: if you are using multi level inheritance, you have to use the top most super class on the
            # left side
            OriginalBundle\Entity\Example: CustomizedBundle\Entity\Example

Hints
-----

* If you are using multi level inheritance, you have to use the top most super class on the left side in the
  configuration under `overridden_entities`.
* If you are using multi level inheritance, the properties of the class in between have to be protected, otherwise you
  will get a `ReflectionException` saying `Property ...::$propertyName does not exist`.
* It is recommended that the original entity is defined as `MappedSuperclass`. You can do so if it is your own code.
  If you want to extend other, third party entities, this should work in most cases. But parts of the mapping will be
  regenerated internally by the `LoadORMMetadataSubscriber` of this bundle and at least some doctrine mapping features
  might not be supported and you might run into issues.
* Using `@ORM\AttributeOverrides` is not supported, instead just override the property in your customized entity class
  and add the ORM mapping for the property in your customized entity as usual. This bundle will then unload the
  original mapping configuration and only use your customized mapping.
* Have a look at the [Tests/Functional/src folder](https://github.com/joschi127/doctrine-entity-override-bundle/tree/master/Tests/Functional/src)
  for some example code.
