<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Exception;
use GraphQL\Doctrine\Factory\MetadataReader\MappingDriverChainAdapter;
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
     * @throws Exception
     * @throws \Doctrine\ORM\ORMException
     *
     * @return Reader
     */
    final protected function getAnnotationReader(): Reader
    {
        $driver = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
        if ($driver instanceof AnnotationDriver) {
            return $driver->getReader();
        }
        if ($driver instanceof MappingDriverChain) {
            return new MappingDriverChainAdapter($driver);
        }

        throw new Exception('graphql-doctrine requires Doctrine to be configured with a `' . AnnotationDriver::class . '`.');
    }
}
