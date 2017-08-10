<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Definition\EntityIDType;
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
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager, array $customTypeMapping = [])
    {
        $this->types = $this->getPhpToGraphQLMapping();
        $this->entityManager = $entityManager;
        $this->objectTypeFactory = new ObjectTypeFactory($this, $entityManager);

        $entityManager->getConfiguration()->newDefaultAnnotationDriver();
        AnnotationRegistry::registerLoader('class_exists');

        foreach ($customTypeMapping as $phpType => $graphQLType) {
            $instance = $this->createInstance($graphQLType, false);
            $this->registerInstance($phpType, $instance);
        }
    }

    private function createInstance(string $className, bool $isInputType): Type
    {
        if (is_a($className, Type::class, true)) {
            return new $className();
        }

        if (!$this->isEntity($className)) {
            throw new \UnexpectedValueException('Given class name `' . $className . '` is not a Doctrine entity. Either register a custom GraphQL type for `' . $className . '` when instantiating `' . self::class . '`, or change the usage of that class to something else.');
        }

        if ($isInputType) {
            return new EntityIDType($this->entityManager, $className);
        }

        return $this->objectTypeFactory->create($className);
    }

    /**
     * Checks if a className is a valid doctrine entity
     *
     * @return bool
     */
    private function isEntity(string $className): bool
    {
        return class_exists($className) && !$this->entityManager->getMetadataFactory()->isTransient($className);
    }

    private function registerInstance(string $key, Type $instance): void
    {
        $this->types[$key] = $instance;
        $this->types[$instance->name] = $instance;
    }

    /**
     * Always return the same instance of `Type` for the given class name
     * @param string $className the class name of either a scalar type (`PostStatus::class`), or an entity (`Post::class`)
     * @return Type
     */
    public function get(string $className, bool $isInputType = false): Type
    {
        $className = ltrim($className, '\\');
        $key = $isInputType && $this->isEntity($className) ? Utils::getIDTypeName($className) : $className;

        if (!isset($this->types[$key])) {
            $instance = $this->createInstance($className, $isInputType);
            $this->registerInstance($key, $instance);
        }

        return $this->types[$key];
    }

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
}
