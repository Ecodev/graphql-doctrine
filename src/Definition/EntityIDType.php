<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use GraphQL\Doctrine\Utils;
use GraphQL\Type\Definition\IDType;

/**
 * A specialized ID type that allows to fetch entity from DB
 */
class EntityIDType extends IDType
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * The entity class name
     * @var string
     */
    private $className;

    public function __construct(EntityManager $entityManager, string $className)
    {
        $this->entityManager = $entityManager;
        $this->className = $className;
        $this->name = Utils::getIDTypeName($className);
        $this->description = 'Automatically generated type to be used as input where an object of type `' . Utils::getTypeName($className) . '` is needed';

        parent::__construct();
    }

    /**
     * Serializes an internal value to include in a response.
     *
     * @param mixed $value
     * @return string
     */
    public function serialize($value)
    {
        $id = $this->entityManager->getClassMetadata($this->className)->getIdentifierValues($value);

        return (string) reset($id);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     * @return mixed A Doctrine entity
     */
    public function parseValue($value)
    {
        $value = parent::parseValue($value);

        return $this->getRepository()->find($value);
    }

    /**
     * Get the repository for our entity
     * @return EntityRepository
     */
    private function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository($this->className);
    }
}
