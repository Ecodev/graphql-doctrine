<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
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
     *
     * @var string
     */
    private $className;

    public function __construct(EntityManager $entityManager, string $className, string $typeName)
    {
        $this->entityManager = $entityManager;
        $this->className = $className;
        $this->name = $typeName;
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
    public function serialize($value): string
    {
        $id = $this->entityManager->getClassMetadata($this->className)->getIdentifierValues($value);

        return (string) reset($id);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     *
     * @return EntityID
     */
    public function parseValue($value): EntityID
    {
        $value = parent::parseValue($value);

        return $this->createEntityID($value);
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input
     *
     * @param \GraphQL\Language\AST\Node $valueNode
     *
     * @return EntityID
     */
    public function parseLiteral($valueNode): EntityID
    {
        $value = parent::parseLiteral($valueNode);

        return $this->createEntityID($value);
    }

    /**
     * Create EntityID to retrieve entity from DB later on
     *
     * @param string $id
     *
     * @return EntityID
     */
    private function createEntityID(string $id): EntityID
    {
        return new EntityID($this->entityManager, $this->className, $id);
    }
}
