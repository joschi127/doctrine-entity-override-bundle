<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="test_example_plain")
 */
class ExamplePlain
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $defaultFieldProtected;   // special handling for private inherited properties required, so let us
                                        // check both, protected and private properties, in our test

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $defaultFieldPrivate;   // special handling for private inherited properties required, so let us
                                        // check both, protected and private properties, in our test

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDefaultFieldProtected()
    {
        return $this->defaultFieldProtected;
    }

    /**
     * @param string $defaultFieldProtected
     */
    public function setDefaultFieldProtected($defaultFieldProtected)
    {
        $this->defaultFieldProtected = $defaultFieldProtected;
    }

    /**
     * @return string
     */
    public function getDefaultFieldPrivate()
    {
        return $this->defaultFieldPrivate;
    }

    /**
     * @param string $defaultFieldPrivate
     */
    public function setDefaultFieldPrivate($defaultFieldPrivate)
    {
        $this->defaultFieldPrivate = $defaultFieldPrivate;
    }
}
