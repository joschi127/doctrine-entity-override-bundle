<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity;

use FOS\UserBundle\Model\User as BaseUser;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="test_user")
 */
class User extends BaseUser
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
    protected $firstName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $lastName;

    /**
     * Overridden property from FOS\UserBundle\Model\User.
     *
     * @var string
     * @ORM\Column(type="string", length=110, nullable=false)
     */
    protected $username;

    /**
     * Overridden property from FOS\UserBundle\Model\User.
     *
     * @var string
     * @ORM\Column(type="string", length=120, nullable=true)
     */
    protected $email;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\Group", cascade={"persist"})
     * @ORM\JoinTable(name="test_user_has_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected $groups;

    /**
     * @var UserActivity
     * @ORM\OneToOne(targetEntity="Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\UserActivity", mappedBy="user", cascade={"persist", "remove", "merge"})
     */
    protected $userActivity;

    public function __construct()
    {
        parent::__construct();

        $this->groups = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return UserActivity
     */
    public function getUserActivity()
    {
        return $this->userActivity;
    }

    /**
     * @param UserActivity $userActivity
     */
    public function setUserActivity($userActivity)
    {
        $userActivity->setUser($this);

        $this->userActivity = $userActivity;
    }
}
