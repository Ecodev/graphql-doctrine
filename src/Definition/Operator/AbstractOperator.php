<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Doctrine\Types;
use GraphQL\Doctrine\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\LeafType;

/**
 * Abstract class that must be implemented to define custom filter options.
 *
 * Once implemented its FQCN should be used via `API\Filter` annotation.
 */
abstract class AbstractOperator extends InputObjectType
{
    /**
     * Types registry
     *
     * @var Types
     */
    protected $types;

    final public function __construct(Types $types, LeafType $leafType)
    {
        $this->types = $types;
        $config = $this->getConfiguration($leafType);

        // Override type name to be predictable
        $config['name'] = Utils::getOperatorTypeName(get_class($this), $leafType);

        parent::__construct($config);
    }

    /**
     * Return the GraphQL type configuration for an `InputObjectType`.
     *
     * This should declare all custom fields needed to apply the filter. In most
     * cases it would include a field such as `value` or `values`, and possibly other
     * more specific fields.
     *
     * The type name, usually configured with the `name` key, should not be defined and
     * will be overridden in all cases. This is because we must have a predictable name
     * that is based only on the class name.
     *
     * @param LeafType $leafType
     *
     * @return array
     */
    abstract protected function getConfiguration(LeafType $leafType): array;

    /**
     * Return the DQL condition to apply the filter
     *
     * In most cases a DQL condition should be returned as a string, but it might be useful to
     * return null if the filter is not applicable (eg: a search term with empty string).
     *
     * The query builder:
     *
     * - MUST NOT be used to apply the condition directly (with `*where()` methods). Instead the condition MUST
     *     be returned as string. Otherwise it will break OR/AND logic of sibling operators.
     * - MAY be used to inspect existing joins and add joins if needed.
     * - SHOULD be used to set query parameter (with the helper of `UniqueNameFactory`)
     *
     * @param UniqueNameFactory $uniqueNameFactory a helper to get unique names to be used in the query
     * @param ClassMetadata $metadata
     * @param QueryBuilder $queryBuilder
     * @param string $alias the alias for the entity on which to apply the filter
     * @param string $field the field for the entity on which to apply the filter
     * @param null|array $args all arguments specific to this operator as declared in its configuration
     *
     * @return null|string
     */
    abstract public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): ?string;
}
