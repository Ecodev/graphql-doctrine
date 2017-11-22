<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an ObjectType from a Doctrine entity
 */
abstract class AbstractTypeFactory
{
    /**
     * @var Types
     */
    protected $types;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(Types $types, EntityManager $entityManager)
    {
        $this->types = $types;
        $this->entityManager = $entityManager;
    }

    /**
     * Create an ObjectType from a Doctrine entity
     *
     * @param string $className class name of Doctrine entity
     *
     * @return Type
     */
    abstract public function create(string $className): Type;

    /**
     * Get the description of a class from the doc block
     *
     * @param string $className
     *
     * @return null|string
     */
    protected function getDescription(string $className): ?string
    {
        $class = new \ReflectionClass($className);

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
