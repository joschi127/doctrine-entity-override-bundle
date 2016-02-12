doctrine-entity-override-bundle
===============================

Symfony bundle which allows to override entities by using inheritance

Configuration
-------------

Add to `app/config/config.yml`:

    # override entities
    joschi127_doctrine_entity_override:
        overridden_entities:
            OriginalBundle\Entity\Example: CustomizedBundle\Entity\Example

