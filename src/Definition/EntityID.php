<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\UserError;

/**
 * An object used to fetch the entity from DB on demand.
 *
 * @template T of object
 */
class EntityID
{
    /**
     * @param class-string<T> $className the entity class name
     * @param null|string $id the entity id
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly string $className,
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
        $entity = $this->entityManager->getRepository($this->className)->find($this->id);
        if (!$entity) {
            throw new UserError('Entity not found for class `' . $this->className . '` and ID `' . $this->id . '`.');
        }

        return $entity;
    }
}
