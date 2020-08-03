<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Utils;
use GraphQL\Error\Error;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * A specialized ID type that allows to fetch entity from DB
 */
final class EntityIDType extends ScalarType
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
     */
    public function parseValue($value, ?array $variables = null): EntityID
    {
        if (!is_string($value) && !is_int($value)) {
            throw new Error('ID cannot represent value: ' . \GraphQL\Utils\Utils::printSafe($value));
        }

        return $this->createEntityID((string) $value);
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input
     *
     * @param \GraphQL\Language\AST\Node $valueNode
     */
    public function parseLiteral($valueNode, ?array $variables = null): EntityID
    {
        if ($valueNode instanceof StringValueNode || $valueNode instanceof IntValueNode) {
            return $this->createEntityID((string) $valueNode->value);
        }

        // Intentionally without message, as all information already in wrapped Exception
        throw new Error();
    }

    /**
     * Create EntityID to retrieve entity from DB later on
     */
    private function createEntityID(string $id): EntityID
    {
        return new EntityID($this->entityManager, $this->className, $id);
    }
}
