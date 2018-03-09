<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="namespace_example")
 */
class AssociationExample
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Group")
     * @ORM\JoinColumn(name="target_id", referencedColumnName="id")
     */
    protected $target;

    /**
     * @return Group
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param Group $target
     */
    public function setTarget(Group $target)
    {
        $this->target = $target;
    }
}