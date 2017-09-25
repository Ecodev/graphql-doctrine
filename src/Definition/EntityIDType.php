<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Utils;
use GraphQL\Error\Error;
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
     *
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
     *
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
     *
     * @return mixed A Doctrine entity
     */
    public function parseValue($value)
    {
        $value = parent::parseValue($value);

        return $this->find($value);
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input
     *
     * @param \GraphQL\Language\AST\Node $valueNode
     *
     * @return mixed
     */
    public function parseLiteral($valueNode)
    {
        $value = parent::parseLiteral($valueNode);

        return $this->find($value);
    }

    /**
     * Get the entity from DB
     *
     * @return mixed entity
     */
    private function find(string $id)
    {
        $entity = $this->entityManager->getRepository($this->className)->find($id);
        if (!$entity) {
            throw new Error('Entity not found for class `' . $this->className . '` and ID `' . $id . '`');
        }

        return $entity;
    }
}
