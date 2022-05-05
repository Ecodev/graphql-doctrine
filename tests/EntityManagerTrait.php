<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Trait to easily set up a dummy entity manager.
 */
trait EntityManagerTrait
{
    private EntityManager $entityManager;

    private function setUpEntityManager(): void
    {
        $config = Setup::createAttributeMetadataConfiguration([__DIR__ . '/Blog/Model'], true);
        $conn = ['url' => 'sqlite:///:memory:'];
        $this->entityManager = EntityManager::create($conn, $config);
    }
}
