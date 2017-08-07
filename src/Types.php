<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
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

    public function __construct(EntityManager $entityManager, array $customTypeMapping = [])
    {
        $this->types = $this->getPhpToGraphQLMapping();
        $this->objectTypeFactory = new ObjectTypeFactory($this, $entityManager);

        $entityManager->getConfiguration()->newDefaultAnnotationDriver();
        AnnotationRegistry::registerLoader('class_exists');

        foreach ($customTypeMapping as $phpType => $graphQLType) {
            $instance = $this->createInstance($graphQLType);
            $this->registerInstance($phpType, $instance);
        }
    }

    private function createInstance(string $className): Type
    {
        if (is_a($className, Type::class, true)) {
            return new $className();
        }

        return $this->objectTypeFactory->create($className);
    }

    private function registerInstance(string $name, Type $instance): void
    {
        $this->types[$name] = $instance;
        $this->types[$instance->name] = $instance;
    }

    /**
     * Always return the same instance of type for the given type name
     * @param string $className the class name of either a scalar type (`PostStatus::class`), or an entity (`Post::class`)
     * @return Type
     */
    public function get(string $className): Type
    {
        if (!isset($this->types[$className])) {
            $instance = $this->createInstance($className);
            $this->registerInstance($className, $instance);
        }

        return $this->types[$className];
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
