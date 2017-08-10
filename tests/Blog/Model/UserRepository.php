<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

/**
 * A fake repository so we don't have to set up a DB
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    public function find($id): ?User
    {
        $id = (int) $id;

        return $id === 123 ? new User($id) : null;
    }
}
