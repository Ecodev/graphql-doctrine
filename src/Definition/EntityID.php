<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\Error;

/**
 * A object used to fetch the entity from DB on demand.
 *
 * @template T of object
 */
class EntityID
{
    /**
     * @param class-string<T> $className
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        /**
         * The entity class name.
         */
        private readonly string $className,
        /**
         * The entity id.
         */
        private readonly ?string $id
    ) {
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
    public function getEntity(): object
    {
        /** @var null|T $entity */
        $entity = $this->entityManager->getRepository($this->className)->find($this->id);
        if (!$entity) {
            throw new Error('Entity not found for class `' . $this->className . '` and ID `' . $this->id . '`.');
        }

        return $entity;
    }
}
