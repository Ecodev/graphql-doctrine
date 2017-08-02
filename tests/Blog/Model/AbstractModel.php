<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Base class for all objects stored in database.
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractModel
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned" = true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    public function getId(): int
    {
        return $this->id;
    }
}
