<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Doctrine\Factory\InputTypeFactory;
use GraphQL\Doctrine\Factory\ObjectTypeFactory;
use GraphQL\Doctrine\Factory\PartialInputTypeFactory;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Psr\Container\ContainerInterface;

/**
 * Registry of types to manage all GraphQL types
 *
 * This is the entry point for the library.
 */
class Types
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
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager, ?ContainerInterface $customTypes = null)
    {
        $this->customTypes = $customTypes;
        $this->types = $this->getPhpToGraphQLMapping();
        $this->entityManager = $entityManager;
        $this->objectTypeFactory = new ObjectTypeFactory($this, $entityManager);
        $this->inputTypeFactory = new InputTypeFactory($this, $entityManager);
        $this->partialInputTypeFactory = new PartialInputTypeFactory($this, $entityManager);

        $entityManager->getConfiguration()->newDefaultAnnotationDriver();
        AnnotationRegistry::registerLoader('class_exists');
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
        $this->throwIfNotEntity($className);
        $key = Utils::getTypeName($className);

        if (!isset($this->types[$key])) {
            $instance = $this->objectTypeFactory->create($className);
            $this->registerInstance($instance);
        }

        return $this->types[$key];
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
        $this->throwIfNotEntity($className);
        $key = Utils::getInputTypeName($className);

        if (!isset($this->types[$key])) {
            $instance = $this->inputTypeFactory->create($className);
            $this->registerInstance($instance);
        }

        return $this->types[$key];
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
        $this->throwIfNotEntity($className);
        $key = Utils::getPartialInputTypeName($className);

        if (!isset($this->types[$key])) {
            $instance = $this->partialInputTypeFactory->create($className);
            $this->registerInstance($instance);
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
            $this->registerInstance($instance);
        }

        return $this->types[$key];
    }

    /**
     * Register the given type in our internal registry with its name
     *
     * @param Type $instance
     */
    private function registerInstance(Type $instance): void
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
}
