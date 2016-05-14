<?php

namespace Joschi127\DoctrineEntityOverrideBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestBase extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var $container
     */
    protected $container;

    public function setUp()
    {
        $kernel = static::createKernel();
        $kernel->boot();
        $this->container = $kernel->getContainer();
        $this->em = $this->container->get('doctrine.orm.entity_manager');
    }

    public function tearDown(){
        /*
         * Close doctrine connections to avoid having a 'too many connections'
         * message when running many tests
         */
        $this->container->get('doctrine')->getConnection()->close();
    
        parent::tearDown();
    }
}
