<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;

/**
 * This is intended to be invalid for graphql-doctrine because it has composite identifiers.
 *
 * @ORM\Entity
 */
final class CompositeIdentifier
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id1;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id2;
}
