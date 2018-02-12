<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\DemoNamespace;

use Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\AssociationExample;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="namespace_example")
 */
class BetterAssociationExample extends AssociationExample
{
}