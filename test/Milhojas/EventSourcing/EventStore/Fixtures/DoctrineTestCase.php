<?php

namespace Test\EventSourcing\EventStore\Fixtures;

// https://vincent.composieux.fr/article/test-your-doctrine-repository-using-a-sqlite-database

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class DoctrineTestCase.
 *
 * This is the base class to load doctrine fixtures using the symfony configuration
 */
class DoctrineTestCase extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $schemaTool = new SchemaTool(static::getEntityManager());
        $metadatas = static::getEntityManager()
                    ->getMetadataFactory()
                    ->getAllMetadata();

        $schemaTool->dropSchema($metadatas);
        $schemaTool->createSchema($metadatas);

        $this->em = $this->getEntityManager();
    }
    /**
     * Executes fixtures.
     *
     * @param \Doctrine\Common\DataFixtures\Loader $loader
     */
    protected function executeFixtures(Loader $loader)
    {
        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());
    }

    /**
     * Load and execute fixtures from a directory.
     *
     * @param string $directory
     */
    protected function loadFixturesFromDirectory($directory)
    {
        $loader = new Loader();
        $loader->loadFromDirectory($directory);
        $this->executeFixtures($loader);
    }

    /**
     * Returns the doctrine orm entity manager.
     *
     * @return object
     */
    protected function getEntityManager()
    {
        $paths = [dirname(dirname(dirname(dirname(dirname(__DIR__))))).'/src/Milhojas/EventSourcing/DTO'];
        $isDevMode = true;

        // the TEST DB connection configuration
        $connectionParams = [
            'driver' => 'pdo_mysql',
            'user' => 'root',
            'password' => 'root',
            'dbname' => 'testmilhojas',
        ];

        $config = Setup::createConfiguration($isDevMode);
        $config->addEntityNamespace('EventStore', 'Milhojas\\EventSourcing\\DTO');

        $driver = new AnnotationDriver(new AnnotationReader(), $paths);

        AnnotationRegistry::registerLoader('class_exists');
        $config->setMetadataDriverImpl($driver);

        $entityManager = EntityManager::create($connectionParams, $config);

        return $entityManager;
    }
}
