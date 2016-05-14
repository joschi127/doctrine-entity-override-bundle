<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\EntityOverride;

use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Doctrine\UserManager;
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

    public function testRepositoryUsingUserManager()
    {
        $this->drop();

        /** @var UserManager $userManager */
        $userManager = $this->container->get('fos_user.user_manager');

        $newUser = $userManager->createUser();
        $this->assertInstanceOf(
            'Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedUser',
            $newUser
        );

        $cleanUser = $this->getNewTestUserObject();
        $newUser->setUsername($cleanUser->getUsername());
        $newUser->setEmail($cleanUser->getEmail());
        $newUser->setPassword($cleanUser->getPassword());
        $newUser->setFirstName($cleanUser->getFirstName());
        $newUser->setLastName($cleanUser->getLastName());
        $newUser->setPhoneNumber($cleanUser->getPhoneNumber());
        $userManager->updateUser($newUser, true);
        $this->em->clear();

        $user = $userManager->findUserByUsername($this->getTestUsername());
        $this->assertInstanceOf(
            'Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedUser',
            $user
        );
        $this->assertEquals($this->getTestUsername(), $user->getUsername());
        $this->assertEquals($cleanUser->getFirstName(), $user->getFirstName());
        $this->assertEquals($cleanUser->getLastName(), $user->getLastName());
        $this->assertEquals($cleanUser->getEmail(), $user->getEmail());
        $this->assertEquals($cleanUser->getPhoneNumber(), $user->getPhoneNumber());
    }

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
        $cleanUser = $this->getNewTestUserObject();
        $this->assertEquals($this->getTestUsername(), $user->getUsername());
        $this->assertEquals($cleanUser->getFirstName(), $user->getFirstName());
        $this->assertEquals($cleanUser->getLastName(), $user->getLastName());
        $this->assertEquals($cleanUser->getEmail(), $user->getEmail());
        $this->assertEquals($cleanUser->getPhoneNumber(), $user->getPhoneNumber());
    }

    protected function createUser()
    {
        $user = $this->getNewTestUserObject();

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

    protected function getNewTestUserObject()
    {
        $user = new CustomizedUser();
        $user->setUsername($this->getTestUsername());
        $user->setEmail('john@doe.com');
        $user->setPassword('');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPhoneNumber('+49 160 1234 5678');

        return $user;
    }

    protected function getTestUsername()
    {
        $reflect = new \ReflectionClass($this);

        return $reflect->getShortName();
    }
}
