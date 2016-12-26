<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity;

use FOS\UserBundle\Model\Group as BaseGroup;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="test_user_activity", indexes={@ORM\Index(name="last_active_at", columns={"last_active_at"})})
 */
class UserActivity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\OneToOne(targetEntity="Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $lastActiveAt;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return \DateTime
     */
    public function getLastActiveAt()
    {
        return $this->lastActiveAt;
    }

    /**
     * @param \DateTime $lastActiveAt
     */
    public function setLastActiveAt($lastActiveAt)
    {
        $this->lastActiveAt = $lastActiveAt;
    }
}