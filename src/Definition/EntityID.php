<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\Error;

/**
 * A object used to fetch the entity from DB on demand
 */
class EntityID
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * The entity class name
     *
     * @var string
     */
    private $className;

    /**
     * The entity id
     *
     * @var null|string
     */
    private $id;

    public function __construct(EntityManager $entityManager, string $className, ?string $id)
    {
        $this->entityManager = $entityManager;
        $this->className = $className;
        $this->id = $id;
    }

    /**
     * Get the ID
     *
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get the entity from DB
     *
     * @throws Error
     *
     * @return mixed entity
     */
    public function getEntity()
    {
        $entity = $this->entityManager->getRepository($this->className)->find($this->id);
        if (!$entity) {
            throw new Error('Entity not found for class `' . $this->className . '` and ID `' . $this->id . '`.');
        }

        return $entity;
    }
}
