<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests\EntityOverride;

use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Doctrine\UserManager;
use Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedExamplePlain;
use Joschi127\DoctrineEntityOverrideBundle\Tests\TestBase;

class CustomizedExamplePlainTest extends TestBase
{
    public function testCustomizedEntity()
    {
        $this->drop();

        $e = new CustomizedExamplePlain();
        $e->setDefaultFieldProtected('test_value_1');
        $e->setDefaultFieldPrivate('test_value_2');
        $e->setAdditionalCustomField('test_value_3');
        $e->setOverriddenField('test_value_4_with_more_than_20_characters'); // override will allow 50 instead of only 20 characters
        $this->em->persist($e);
        $this->em->flush();
        $this->em->clear();

        $this->fetchAndCheckCustomizedEntityBy(['defaultFieldProtected' => 'test_value_1']);
        $this->em->clear();

        $this->fetchAndCheckCustomizedEntityBy(['defaultFieldPrivate' => 'test_value_2']);
        $this->em->clear();

        $this->fetchAndCheckCustomizedEntityBy(['additionalCustomField' => 'test_value_3']);
        $this->em->clear();

        $this->fetchAndCheckCustomizedEntityBy(['overriddenField' => 'test_value_4_with_more_than_20_characters']);
        $this->em->clear();
    }

    protected function fetchAndCheckCustomizedEntityBy(array $criteria)
    {
        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository('Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedExamplePlain');
        /** @var CustomizedExamplePlain $e */
        $e = $repository->findOneBy($criteria);

        $this->assertInstanceOf(
            'Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedExamplePlain',
            $e
        );
        $this->assertEquals('test_value_1', $e->getDefaultFieldProtected());
        $this->assertEquals('test_value_2', $e->getDefaultFieldPrivate());
        $this->assertEquals('test_value_3', $e->getAdditionalCustomField());
        $this->assertEquals('test_value_4_with_more_than_20_characters', $e->getOverriddenField());
    }

    protected function drop()
    {
        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository('Joschi127\DoctrineEntityOverrideBundle\Tests\Functional\src\Entity\CustomizedExamplePlain');
        foreach($repository->findBy(['defaultFieldProtected' => 'test_value_1']) as $e) {
            $this->em->remove($e);
        }

        $this->em->flush();
        $this->em->clear();
    }
}
