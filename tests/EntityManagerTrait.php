<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Trait to easily set up a dummy entity manager
 */
trait EntityManagerTrait
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    private function setUpEntityManager(): void
    {
        // Create the entity manager
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/Blog/Model'], true, null, null, false);
        $conn = ['url' => 'sqlite:///:memory:'];
        $this->entityManager = EntityManager::create($conn, $config);
    }
}
