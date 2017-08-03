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
        if (!$this->isValid($className)) {
            throw new \UnexpectedValueException('Given class name `' . $className . '` is not a Doctrine entity. Either register a custom GraphQL type for `' . $className . '` when instantiating `' . Types::class . '`, or change the usage of that class to something else.');
        }

        $class = new \ReflectionClass($className);

        $typeName = $this->getTypeName($class);
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
     * Checks if a className is a valid doctrine entity
     *
     * @return bool
     */
    private function isValid(string $className): bool
    {
        return !$this->entityManager->getMetadataFactory()->isTransient($className);
    }

    /**
     * Get the GraphQL type name from the PHP class
     * @param ReflectionClass $class
     * @return string
     */
    private function getTypeName(ReflectionClass $class): string
    {
        return $class->getShortName();
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
