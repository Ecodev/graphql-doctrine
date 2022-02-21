<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Registry of types to manage all GraphQL types.
 *
 * This interface purpose is to be able to mock implementations in tests. It is not meant
 * to create alternative implementations for production use.
 */
interface TypesInterface
{
    /**
     * Returns whether a type exists for the given key.
     */
    public function has(string $key): bool;

    /**
     * Always return the same instance of `Type` for the given key.
     *
     * It will first look for the type in the custom types container, and then
     * use automatically generated types. This allow for custom types to override
     * automatic ones.
     *
     * @param string $key the key the type was registered with (eg: "Post", "PostInput", "PostPartialInput" or "PostStatus")
     */
    public function get(string $key): Type;

    /**
     * Returns an output type for the given entity.
     *
     * All entity getter methods will be exposed, unless specified otherwise
     * with annotations.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getOutput(string $className): ObjectType;

    /**
     * Returns an input type for the given entity.
     *
     * This would typically be used in mutations to create new entities.
     *
     * All entity setter methods will be exposed, unless specified otherwise
     * with annotations.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getInput(string $className): InputObjectType;

    /**
     * Returns a partial input type for the given entity.
     *
     * This would typically be used in mutations to update existing entities.
     *
     * All entity setter methods will be exposed, unless specified otherwise
     * with annotations. But they will all be marked as optional and without
     * default values. So this allow the API client to specify only some fields
     * to be updated, and not necessarily all of them at once.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getPartialInput(string $className): InputObjectType;

    /**
     * Returns a filter input type for the given entity.
     *
     * This would typically be used to filter queries.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getFilter(string $className): InputObjectType;

    /**
     * Returns a sorting input type for the given entity.
     *
     * This would typically be used to sort queries.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getSorting(string $className): ListOfType;

    /**
     * Returns an special ID type for the given entity.
     *
     * This is mostly useful for internal usage when a getter has an entity
     * as parameter. This type will automatically load the entity from DB, so
     * the resolve functions can use a real instance of entity instead of an ID.
     * But this can also be used to build your own schema and thus avoid
     * manually fetching objects from database for simple cases.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getId(string $className): EntityIDType;

    /**
     * Create and return a query builder that is filtered and sorted for the given entity.
     *
     * Typical usage would be to call this method in your query resolver with the filter and sorting arguments directly
     * coming from GraphQL.
     *
     * You may apply further pagination according to your needs before executing the query.
     *
     * Filter and sorting arguments are assumed to be valid and complete as the validation should have happened when
     * parsing the GraphQL query.
     *
     * @param class-string $className
     */
    public function createFilteredQueryBuilder(string $className, array $filter, array $sorting): QueryBuilder;
}
