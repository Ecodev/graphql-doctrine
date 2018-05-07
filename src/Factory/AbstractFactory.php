<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Exception;
use GraphQL\Doctrine\Types;

/**
 * Abstract factory to be aware of types and entityManager
 */
abstract class AbstractFactory
{
    /**
     * @var Types
     */
    protected $types;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(Types $types, EntityManager $entityManager)
    {
        $this->types = $types;
        $this->entityManager = $entityManager;
    }

    /**
     * Get annotation reader
     *
     * @return Reader
     */
    final protected function getAnnotationReader(): Reader
    {
        $driver = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
        if (!$driver instanceof AnnotationDriver) {
            throw new Exception('graphql-doctrine requires Doctrine to be configured with a `' . AnnotationDriver::class . '`.');
        }

        return $driver->getReader();
    }
}
