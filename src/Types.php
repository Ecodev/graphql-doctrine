<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Doctrine\Factory\InputTypeFactory;
use GraphQL\Doctrine\Factory\ObjectTypeFactory;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Registry of types to manage all GraphQL types
 *
 * This is the entry point for the library.
 */
class Types
{
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
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager, array $customTypeMapping = [])
    {
        $this->types = $this->getPhpToGraphQLMapping();
        $this->entityManager = $entityManager;
        $this->objectTypeFactory = new ObjectTypeFactory($this, $entityManager);
        $this->inputTypeFactory = new InputTypeFactory($this, $entityManager);

        $entityManager->getConfiguration()->newDefaultAnnotationDriver();
        AnnotationRegistry::registerLoader('class_exists');

        foreach ($customTypeMapping as $phpType => $graphQLType) {
            $instance = $this->createInstance($graphQLType);
            $this->registerInstance($phpType, $instance);
            $this->registerInstance($graphQLType, $instance);
        }
    }

    /**
     * Always return the same instance of `Type` for the given class name
     *
     * All entity getter methods will be exposed, unless specified otherwise
     * with annotations.
     *
     * @param string $className the class name of either a scalar type (`PostStatus::class`), or an entity (`Post::class`)
     *
     * @return Type
     */
    public function get(string $className): Type
    {
        $key = $this->normalizedClassName($className);

        if (!isset($this->types[$key])) {
            $instance = $this->createInstance($className);
            $this->registerInstance($key, $instance);
        }

        return $this->types[$key];
    }

    /**
     * Returns an input type for the given entity to be used in mutations
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
        $this->throwIfNotEntity($className);
        $key = Utils::getInputTypeName($className);

        if (!isset($this->types[$key])) {
            $instance = $this->inputTypeFactory->create($className);
            $this->registerInstance($key, $instance);
        }

        return $this->types[$key];
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
        $this->throwIfNotEntity($className);
        $key = Utils::getIDTypeName($className);

        if (!isset($this->types[$key])) {
            $instance = new EntityIDType($this->entityManager, $className);
            $this->registerInstance($key, $instance);
        }

        return $this->types[$key];
    }

    /**
     * Register the given type in our internal registry
     *
     * @param string $key
     * @param Type $instance
     */
    private function registerInstance(string $key, Type $instance): void
    {
        $this->types[$key] = $instance;
        $this->types[$instance->name] = $instance;
    }

    /**
     * Create an instance of either a custom, scalar or ObjectType
     *
     * @param string $className
     *
     * @return Type
     */
    private function createInstance(string $className): Type
    {
        if (is_a($className, Type::class, true)) {
            return new $className();
        }

        $this->throwIfNotEntity($className);

        return $this->objectTypeFactory->create($className);
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
     * Returns the list of native GraphQL types
     *
     * @return array
     */
    private function getPhpToGraphQLMapping(): array
    {
        return [
            'id' => Type::id(),
            'bool' => Type::boolean(),
            'int' => Type::int(),
            'float' => Type::float(),
            'string' => Type::string(),
        ];
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
     * Remove the leading `\` that may exists in FQCN
     *
     * @param string $className
     *
     * @return string
     */
    private function normalizedClassName(string $className): string
    {
        return ltrim($className, '\\');
    }
}
