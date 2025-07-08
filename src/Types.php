<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

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
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

/**
 * Registry of types to manage all GraphQL types.
 *
 * This is the entry point for the library.
 */
final class Types implements TypesInterface
{
    /**
     * @var array<string, NamedType&Type> mapping of type name to type instances
     */
    private array $types = [];

    private readonly ObjectTypeFactory $objectTypeFactory;

    private readonly InputTypeFactory $inputTypeFactory;

    private readonly PartialInputTypeFactory $partialInputTypeFactory;

    private readonly FilterTypeFactory $filterTypeFactory;

    private readonly FilteredQueryBuilderFactory $filteredQueryBuilderFactory;

    private readonly SortingTypeFactory $sortingTypeFactory;

    private readonly EntityIDTypeFactory $entityIDTypeFactory;

    private readonly JoinOnTypeFactory $joinOnTypeFactory;

    private readonly FilterGroupJoinTypeFactory $filterGroupJoinTypeFactory;

    private readonly FilterGroupConditionTypeFactory $filterGroupConditionTypeFactory;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ?ContainerInterface $customTypes = null,
    ) {
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

        $this->initializeInternalTypes();
    }

    public function has(string $key): bool
    {
        return $this->customTypes && $this->customTypes->has($key) || array_key_exists($key, $this->types);
    }

    public function get(string $key): Type&NamedType
    {
        if ($this->customTypes && $this->customTypes->has($key)) {
            /** @var NamedType&Type $t */
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
     * Get a type from internal registry, and create it via the factory if needed.
     *
     * @param class-string $className
     */
    private function getViaFactory(string $className, string $typeName, AbstractTypeFactory $factory): Type&NamedType
    {
        $this->throwIfNotEntity($className);

        if (!isset($this->types[$typeName])) {
            $instance = $factory->create($className, $typeName);
            $this->registerInstance($instance);
        }

        return $this->types[$typeName];
    }

    public function getOutput(string $className): ObjectType
    {
        /** @var ObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className), $this->objectTypeFactory);

        return $type;
    }

    public function getInput(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'Input', $this->inputTypeFactory);

        return $type;
    }

    public function getPartialInput(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'PartialInput', $this->partialInputTypeFactory);

        return $type;
    }

    public function getFilter(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'Filter', $this->filterTypeFactory);

        return $type;
    }

    public function getSorting(string $className): ListOfType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'Sorting', $this->sortingTypeFactory);

        return Type::listOf(Type::nonNull($type));
    }

    /**
     * Returns a joinOn input type for the given entity.
     *
     * This is for internal use only.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getJoinOn(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, 'JoinOn' . Utils::getTypeName($className), $this->joinOnTypeFactory);

        return $type;
    }

    /**
     * Returns a joins input type for the given entity.
     *
     * This is for internal use only.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getFilterGroupJoin(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'FilterGroupJoin', $this->filterGroupJoinTypeFactory);

        return $type;
    }

    /**
     * Returns a condition input type for the given entity.
     *
     * This is for internal use only.
     *
     * @param class-string $className the class name of an entity (`Post::class`)
     */
    public function getFilterGroupCondition(string $className): InputObjectType
    {
        /** @var InputObjectType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'FilterGroupCondition', $this->filterGroupConditionTypeFactory);

        return $type;
    }

    public function getId(string $className): EntityIDType
    {
        /** @var EntityIDType $type */
        $type = $this->getViaFactory($className, Utils::getTypeName($className) . 'ID', $this->entityIDTypeFactory);

        return $type;
    }

    /**
     * Returns an operator input type.
     *
     * This is for internal use only.
     *
     * @param class-string<AbstractOperator> $className the class name of an operator (`EqualOperatorType::class`)
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
     * Register the given type in our internal registry with its name.
     *
     * This is for internal use only. You should declare custom types via the constructor, not this method.
     */
    public function registerInstance(Type&NamedType $instance): void
    {
        $this->types[$instance->name()] = $instance;
    }

    /**
     * Checks if a className is a valid doctrine entity.
     */
    public function isEntity(string $className): bool
    {
        return class_exists($className) && !$this->entityManager->getMetadataFactory()->isTransient($className);
    }

    /**
     * Initialize internal types for common needs.
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
     * Throw an exception if the class name is not Doctrine entity.
     */
    private function throwIfNotEntity(string $className): void
    {
        if (!$this->isEntity($className)) {
            throw new UnexpectedValueException('Given class name `' . $className . '` is not a Doctrine entity. Either register a custom GraphQL type for `' . $className . '` when instantiating `' . self::class . '`, or change the usage of that class to something else.');
        }
    }

    public function createFilteredQueryBuilder(string $className, array $filter, array $sorting): QueryBuilder
    {
        return $this->filteredQueryBuilderFactory->create($className, $filter, $sorting);
    }

    public function loadType(string $typeName, string $namespace): ?Type
    {
        if ($this->has($typeName)) {
            return $this->get($typeName);
        }

        if (preg_match('~^(?<shortName>.*)(?<kind>PartialInput)$~', $typeName, $m)
            || preg_match('~^(?<shortName>.*)(?<kind>Input|PartialInput|Filter|Sorting|FilterGroupJoin|FilterGroupCondition|ID)$~', $typeName, $m)
            || preg_match('~^(?<kind>JoinOn)(?<shortName>.*)$~', $typeName, $m)
            || preg_match('~^(?<shortName>.*)$~', $typeName, $m)) {
            $shortName = $m['shortName'];
            $kind = $m['kind'] ?? '';

            /** @var class-string $className */
            $className = $namespace . '\\' . $shortName;

            if ($this->isEntity($className)) {
                return match ($kind) {
                    'Input' => $this->getViaFactory($className, $typeName, $this->inputTypeFactory),
                    'PartialInput' => $this->getViaFactory($className, $typeName, $this->partialInputTypeFactory),
                    'Filter' => $this->getViaFactory($className, $typeName, $this->filterTypeFactory),
                    'Sorting' => $this->getViaFactory($className, $typeName, $this->sortingTypeFactory),
                    'JoinOn' => $this->getViaFactory($className, $typeName, $this->joinOnTypeFactory),
                    'FilterGroupJoin' => $this->getViaFactory($className, $typeName, $this->filterGroupJoinTypeFactory),
                    'FilterGroupCondition' => $this->getViaFactory($className, $typeName, $this->filterGroupConditionTypeFactory),
                    'ID' => $this->getViaFactory($className, $typeName, $this->entityIDTypeFactory),
                    '' => $this->getViaFactory($className, $typeName, $this->objectTypeFactory),
                    default => throw new Exception("Unsupported kind of type `$kind` when trying to load type `$typeName`"),
                };
            }
        }

        return null;
    }
}
