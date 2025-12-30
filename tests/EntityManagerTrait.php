<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

/**
 * Trait to easily set up a dummy entity manager.
 */
trait EntityManagerTrait
{
    private EntityManager $entityManager;

    private function setUpEntityManager(): void
    {
        $config = ORMSetup::createAttributeMetadataConfig([__DIR__ . '/Blog/Model'], true);
        $config->enableNativeLazyObjects(true);
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);

        $this->entityManager = new EntityManager($connection, $config);
    }
}
