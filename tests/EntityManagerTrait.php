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
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Blog/Model'], true);
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);

        $this->entityManager = new EntityManager($connection, $config);
    }
}
