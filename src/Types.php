<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Doctrine\Definition\JoinTypeType;
use GraphQL\Doctrine\Definition\LogicalOperatorType;
use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Definition\SortingOrderType;
use GraphQL\Doctrine\Factory\FilteredQueryBuilderFactory;
use GraphQL\Doctrine\Factory\Type\AbstractTypeFactory;
use GraphQL\Doctrine\Factory\Type\EntityIDTypeFactory;
use GraphQL\Doctrine\Factory\Type\FilterGroupConditionTypeFactory;
use GraphQL\Doctrine\Factory\Type\FilterGroupJoinTypeFactory;
use GraphQL\Doctrine\Factory\Type\FilterTypeFactory;
use GraphQL\Doctrine\Factory\Type\InputTypeFactory;
use GraphQL\Doctrine\Factory\Type\JoinOnTypeFactory;
use GraphQL\Doctrine\Factory\Type\ObjectTypeFactory;
use GraphQL\Doctrine\Factory\Type\PartialInputTypeFactory;
use GraphQL\Doctrine\Factory\Type\SortingTypeFactory;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Psr\Container\ContainerInterface;

/**
 * Registry of types to manage all GraphQL types
 *
 * This is the entry point for the library.
 */
final class Types
{
    /**
     * @var null|ContainerInterface
     */
    private $customTypes;

    /**
     * @var array mapping of type name to type instances
     */
    private $types = [];

    /**
     * @var ObjectTypeFactory
     */
    private $objectTypeFactory;

    /**
     * @var InputTypeFactory
     */
    private $inputTypeFactory;

    /**
     * @var PartialInputTypeFactory
     */
    private $partialInputTypeFactory;

    /**
     * @var FilterTypeFactory
     */
    private $filterTypeFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var FilteredQueryBuilderFactory
     */
    private $filteredQueryBuilderFactory;

    /**
     * @var SortingTypeFactory
     */
    private $sortingTypeFactory;

    /**
     * @var EntityIDTypeFactory
     */
    private $entityIDTypeFactory;

    /**
     * @var JoinOnTypeFactory
     */
    private $joinOnTypeFactory;

    /**
     * @var FilterGroupJoinTypeFactory
     */
    private $filterGroupJoinTypeFactory;

    /**
     * @var FilterGroupConditionTypeFactory
     */
    private $filterGroupConditionTypeFactory;

    public function __construct(EntityManager $entityManager, ?ContainerInterface $customTypes = null)
    {
        $this->customTypes = $customTypes;
        $this->entityManager = $entityManager;
        $this->objectTypeFactory = new ObjectTypeFactory($this, $entityManager);
        $this->inputTypeFactory = new InputTypeFactory($this, $entityManager);
        $this->partialInputTypeFactory = new PartialInputTypeFactory($this, $entityManager);
        $this->sortingTypeFactory = new SortingTypeFactory($this, $entityManager);
        $this->entityIDTypeFactory = new EntityIDTypeFactory($this, $entityManager);
        $this->filterGroupJoinTypeFactory = new FilterGroupJoinTypeFactory($this, $entityManager);
        $this->filterGroupConditionTypeFactory = new FilterGroupConditionTypeFactory($this, $entityManager);
        $this->filteredQueryBuilderFactory = new FilteredQueryBuilderFactory($this, $entityManager, $this->sortingTypeFactory);
        $this->filterTypeFactory = new FilterTypeFactory($this, $entityManager, $this->filterGroupJoinTypeFactory, $this->filterGroupConditionTypeFactory);
        $this->joinOnTypeFactory = new JoinOnTypeFactory($this, $entityManager, $this->filterGroupJoinTypeFactory, $this->filterGroupConditionTypeFactory);

        $entityManager->getConfiguration()->newDefaultAnnotationDriver();
        AnnotationRegistry::registerLoader('class_exists');

        $this->initializeInternalTypes();
    }

    /**
     * Returns whether a type exists for the given key
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->customTypes && $this->customTypes->has($key) || array_key_exists($key, $this->types);
    }

    /**
     * Always return the same instance of `Type` for the given key
     *
     * It will first look for the type in the custom types container, and then
     * use automatically generated types. This allow for custom types to override
     * automatic ones.
     *
     * @param string $key the key the type was registered with (eg: "Post", "PostInput", "PostPartialInput" or "PostStatus")
     *
     * @return Type
     */
    public function get(string $key): Type
    {
        if ($this->customTypes && $this->customTypes->has($key)) {
            $t = $this->customTypes->get($key);
            $this->registerInstance($t);

            return $t;
        }

        if (array_key_exists($key, $this->types)) {
            return $this->types[$key];
        }

        throw new Exception('No type registered with key `' . $key . '`. Either correct the usage, or register it in your custom types container when instantiating `' . self::class . '`.');
    }

    /**
     * Get a type from internal registry, and create it via the factory if needed
     *
     * @param string $className
     * @param string $typeName
     * @param AbstractTypeFactory $factory
     *
     * @return Type
     */
    private function getViaFactory(string $className, string $typeName, AbstractTypeFactory $factory): Type
    {
        $this->throwIfNotEntity($className);

        if (!isset($this->types[$typeName])) {
            $instance = $factory->create($className, $typeName);
            $this->registerInstance($instance);
        }

        return $this->types[$typeName];
    }

    /**
     * Returns an output type for the given entity
     *
     * All entity getter methods will be exposed, unless specified otherwise
     * with annotations.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return ObjectType
     */
    public function getOutput(string $className): ObjectType
    {
        /** @var ObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className), $this->objectTypeFactory);

        return $type;
    }

    /**
     * Returns an input type for the given entity
     *
     * This would typically be used in mutations to create new entities.
     *
     * All entity setter methods will be exposed, unless specified otherwise
     * with annotations.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return InputObjectType
     */
    public function getInput(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'Input', $this->inputTypeFactory);

        return $type;
    }

    /**
     * Returns a partial input type for the given entity
     *
     * This would typically be used in mutations to update existing entities.
     *
     * All entity setter methods will be exposed, unless specified otherwise
     * with annotations. But they will all be marked as optional and without
     * default values. So this allow the API client to specify only some fields
     * to be updated, and not necessarily all of them at once.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return InputObjectType
     */
    public function getPartialInput(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'PartialInput', $this->partialInputTypeFactory);

        return $type;
    }

    /**
     * Returns a filter input type for the given entity
     *
     * This would typically be used to filter queries.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return InputObjectType
     */
    public function getFilter(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'Filter', $this->filterTypeFactory);

        return $type;
    }

    /**
     * Returns a sorting input type for the given entity
     *
     * This would typically be used to sort queries.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return ListOfType
     */
    public function getSorting(string $className): ListOfType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'Sorting', $this->sortingTypeFactory);

        return Type::listOf(Type::nonNull($type));
    }

    /**
     * Returns a joinOn input type for the given entity
     *
     * This is for internal use only.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return InputObjectType
     */
    public function getJoinOn(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, 'JoinOn' . Utils::getTypeName($className), $this->joinOnTypeFactory);

        return $type;
    }

    /**
     * Returns a joins input type for the given entity
     *
     * This is for internal use only.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return InputObjectType
     */
    public function getFilterGroupJoin(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'FilterGroupJoin', $this->filterGroupJoinTypeFactory);

        return $type;
    }

    /**
     * Returns a condition input type for the given entity
     *
     * This is for internal use only.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return InputObjectType
     */
    public function getFilterGroupCondition(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'FilterGroupCondition', $this->filterGroupConditionTypeFactory);

        return $type;
    }

    /**
     * Returns an special ID type for the given entity
     *
     * This is mostly useful for internal usage when a getter has an entity
     * as parameter. This type will automatically load the entity from DB, so
     * the resolve functions can use a real instance of entity instead of an ID.
     * But this can also be used to build your own schema and thus avoid
     * manually fetching objects from database for simple cases.
     *
     * @param string $className the class name of an entity (`Post::class`)
     *
     * @return EntityIDType
     */
    public function getId(string $className): EntityIDType
    {
        /** @var EntityIDType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'ID', $this->entityIDTypeFactory);

        return $type;
    }

    /**
     * Returns an operator input type
     *
     * This is for internal use only.
     *
     * @param string $className the class name of an operator (`EqualOperatorType::class`)
     * @param LeafType $type
     *
     * @throws Exception
     *
     * @return AbstractOperator
     */
    public function getOperator(string $className, LeafType $type): AbstractOperator
    {
        if (!is_a($className, AbstractOperator::class, true)) {
            throw new Exception('Expects a FQCN implementing `' . AbstractOperator::class . '`, but instead got: ' . $className);
        }

        $key = Utils::getOperatorTypeName($className, $type);

        if (!isset($this->types[$key])) {
            $instance = new $className($this, $type);
            $this->registerInstance($instance);
        }

        return $this->types[$key];
    }

    /**
     * Register the given type in our internal registry with its name
     *
     * This is for internal use only. You should declare custom types via the constructor, not this method.
     *
     * @param Type $instance
     */
    public function registerInstance(Type $instance): void
    {
        $this->types[$instance->name] = $instance;
    }

    /**
     * Checks if a className is a valid doctrine entity
     *
     * @param string $className
     *
     * @return bool
     */
    public function isEntity(string $className): bool
    {
        return class_exists($className) && !$this->entityManager->getMetadataFactory()->isTransient($className);
    }

    /**
     * Initialize internal types for common needs
     */
    private function initializeInternalTypes(): void
    {
        $phpToGraphQLMapping = [
            // PHP types
            'id' => Type::id(),
            'bool' => Type::boolean(),
            'int' => Type::int(),
            'float' => Type::float(),
            'string' => Type::string(),

            // Doctrine types
            'boolean' => Type::boolean(),
            'integer' => Type::int(),
            'smallint' => Type::int(),
            'bigint' => Type::int(),
            'decimal' => Type::string(),
            'text' => Type::string(),
        ];

        $this->types = $phpToGraphQLMapping;
        $this->registerInstance(new LogicalOperatorType());
        $this->registerInstance(new JoinTypeType());
        $this->registerInstance(new SortingOrderType());
    }

    /**
     * Throw an exception if the class name is not Doctrine entity
     *
     * @param string $className
     *
     * @throws \UnexpectedValueException
     */
    private function throwIfNotEntity(string $className): void
    {
        if (!$this->isEntity($className)) {
            throw new \UnexpectedValueException('Given class name `' . $className . '` is not a Doctrine entity. Either register a custom GraphQL type for `' . $className . '` when instantiating `' . self::class . '`, or change the usage of that class to something else.');
        }
    }

    /**
     * Create and return a query builder that is filtered and sorted for the given entity
     *
     * Typical usage would be to call this method in your query resolver with the filter and sorting arguments directly
     * coming from GraphQL.
     *
     * You may apply further pagination according to your needs before executing the query.
     *
     * Filter and sorting arguments are assumed to be valid and complete as the validation should have happened when
     * parsing the GraphQL query.
     *
     * @param string $className
     * @param array $filter
     * @param array $sorting
     *
     * @return QueryBuilder
     */
    public function createFilteredQueryBuilder(string $className, array $filter, array $sorting): QueryBuilder
    {
        return $this->filteredQueryBuilderFactory->create($className, $filter, $sorting);
    }
}
