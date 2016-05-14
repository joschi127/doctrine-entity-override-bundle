<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\EntityOverride;

use Doctrine\ORM\EntityRepository;
use Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedUser;
use Joschi127\DoctrineEntityOverrideBundle\Tests\TestBase;

class CustomizedUserTest extends TestBase
{
    public function testRepositoryUsingCustomizedEntityName()
    {
        $this->doTestRepository('Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedUser');
    }

//    public function testRepositoryUsingOriginalEntityName()
//    {
//        $this->doTestRepository('Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\User');
//    }

    protected function doTestRepository($entityName)
    {
        $this->drop();
        $this->createUser();

        /** @var EntityRepository $userRepository */
        $userRepository = $this->em->getRepository($entityName);
        /** @var CustomizedUser $user */
        $user = $userRepository->findOneBy([
            'username' => $this->getTestUsername()
        ]);

        $this->assertInstanceOf(
            'Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedUser',
            $user
        );
        $this->assertEquals($this->getTestUsername(), $user->getUsername());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('john@doe.com', $user->getEmail());
        $this->assertEquals('+49 160 1234 5678', $user->getPhoneNumber());
    }

    protected function createUser()
    {
        $user = new CustomizedUser();
        $user->setUsername($this->getTestUsername());
        $user->setEmail('john@doe.com');
        $user->setPassword('test');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPhoneNumber('+49 160 1234 5678');

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
    }

    protected function drop()
    {
        $userRepository = $this->em->getRepository('Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedUser');
        try {
            $user = $userRepository->findOneBy([
                'username' => $this->getTestUsername()
            ]);
        } catch(\Exception $e) {
            var_dump($e);
            return;
        }

        if ($user) {
            $this->em->remove($user);
        }

        $this->em->flush();
        $this->em->clear();
    }

    protected function getTestUsername()
    {
        $reflect = new \ReflectionClass($this);

        return $reflect->getShortName();
    }
}
