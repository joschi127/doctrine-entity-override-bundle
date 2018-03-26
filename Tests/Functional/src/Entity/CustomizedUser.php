<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="test_user")
 *
 */
class CustomizedUser extends User
{
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $phoneNumber;

    /**
     * Overridden property from FOS\UserBundle\Model\User and Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\User.
     *
     * @var string
     * @ORM\Column(type="string", length=220, nullable=false)
     */
    protected $email;

    /**
     * Overridden property from FOS\UserBundle\Model\User.
     *
     * @var string
     * @ORM\Column(type="string", length=230, nullable=true)
     */
    protected $password;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }
}
