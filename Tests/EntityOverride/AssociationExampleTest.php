<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\EntityOverride;


use Doctrine\ORM\Mapping\ClassMetadata;
use Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\DemoNamespace\BetterAssociationExample;
use Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\Group;
use Joschi127\DoctrineEntityOverrideBundle\Tests\TestBase;

class AssociationExampleTest extends TestBase
{
    public function testAbsoluteTargetEntityInMetadata()
    {
        $repository = $this->em->getRepository(BetterAssociationExample::class);
        $relfMethod = new \ReflectionMethod($repository, 'getClassMetadata');
        $relfMethod->setAccessible(true);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $relfMethod->invoke($repository);

        self::assertEquals(Group::class, $classMetadata->associationMappings['target']['targetEntity']);
    }

    public function testAbsoluteTargetEntityInAction()
    {
        $group = new Group("AssocTestGroup");
        $assoc = new BetterAssociationExample();
        $assoc->setTarget($group);

        $this->em->persist($group);
        $this->em->persist($assoc);
        $this->em->flush();

        $this->em->getRepository(BetterAssociationExample::class)->findAll();
    }
}