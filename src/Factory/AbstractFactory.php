<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use GraphQL\Doctrine\Annotation\Exclude;
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

    /**
     * Returns whether the property is excluded
     *
     * @param ClassMetadata $metadata
     * @param string $propertyName
     *
     * @return bool
     */
    final protected function isPropertyExcluded(ClassMetadata $metadata, string $propertyName): bool
    {
        $property = $metadata->getReflectionProperty($propertyName);
        $exclude = $this->getAnnotationReader()->getPropertyAnnotation($property, Exclude::class);

        return $exclude !== null;
    }
}
