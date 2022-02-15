<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\Error;

/**
 * A object used to fetch the entity from DB on demand.
 *
 * @template T
 */
class EntityID
{
    private EntityManager $entityManager;

    /**
     * The entity class name.
     *
     * @var class-string<T>
     */
    private $className;

    /**
     * The entity id.
     */
    private ?string $id;

    /**
     * @param class-string<T> $className
     */
    public function __construct(EntityManager $entityManager, string $className, ?string $id)
    {
        $this->entityManager = $entityManager;
        $this->className = $className;
        $this->id = $id;
    }

    /**
     * Get the ID.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get the entity from DB.
     *
     * @return T entity
     */
    public function getEntity()
    {
        /** @var null|T $entity */
        $entity = $this->entityManager->getRepository($this->className)->find($this->id);
        if (!$entity) {
            throw new Error('Entity not found for class `' . $this->className . '` and ID `' . $this->id . '`.');
        }

        return $entity;
    }
}
