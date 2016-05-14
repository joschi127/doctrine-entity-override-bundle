doctrine-entity-override-bundle
===============================

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

