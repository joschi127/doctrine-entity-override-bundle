<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="test_example_plain")
 */
class CustomizedExamplePlain extends ExamplePlain
{
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $additionalCustomField;

    /**
     * @return string
     */
    public function getAdditionalCustomField()
    {
        return $this->additionalCustomField;
    }

    /**
     * @param string $additionalCustomField
     */
    public function setAdditionalCustomField($additionalCustomField)
    {
        $this->additionalCustomField = $additionalCustomField;
    }
}
