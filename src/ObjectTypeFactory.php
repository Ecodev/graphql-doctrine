<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\ObjectType;
use ReflectionClass;

/**
 * A factory to create an ObjectType from a Doctrine entity
 */
class ObjectTypeFactory
{
    /**
     * @var Types
     */
    private $types;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(Types $types, EntityManager $entityManager)
    {
        $this->types = $types;
        $this->entityManager = $entityManager;
    }

    /**
     * Create an ObjectType from a Doctrine entity
     * @param string $className class name of Doctrine entity
     * @throws \UnexpectedValueException
     * @return ObjectType
     */
    public function create(string $className): ObjectType
    {
        $class = new \ReflectionClass($className);

        $typeName = Utils::getTypeName($className);
        $description = $this->getDescription($class);

        $fieldGetter = function () use ($className): array {
            $factory = new FieldsConfigurationFactory($this->types, $this->entityManager);
            $configuration = $factory->create($className);

            return $configuration;
        };

        return new ObjectType([
            'name' => $typeName,
            'description' => $description,
            'fields' => $fieldGetter,
        ]);
    }

    /**
     * Get the description of a class from the docblock
     * @param ReflectionClass $class
     * @return string|null
     */
    private function getDescription(ReflectionClass $class): ?string
    {
        $comment = $class->getDocComment();

        // Remove the comment markers
        $comment = preg_replace('~^\s*(/\*\*|\* ?|\*/)~m', '', $comment);

        // Keep everything before the first annotation
        $comment = trim(explode('@', $comment)[0]);

        if (!$comment) {
            $comment = null;
        }

        return $comment;
    }
}
