<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Repository;

use Doctrine\ORM\EntityRepository;
use GraphQLTests\Doctrine\Blog\Model\User;

/**
 * A fake repository so we don't have to set up a DB.
 */
final class UserRepository extends EntityRepository
{
    /**
     * @param int $id
     * @param null|int $lockMode
     * @param null|int $lockVersion
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?User
    {
        $id = (int) $id;

        return $id === 123 ? new User($id) : null;
    }
}
