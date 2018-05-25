<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * A many to many from author to post
 * This is intended to be invalid for GraphQL because it has a compound
 * primary key and is not a Many to Many join table in native Doctrine.
 *
 * @ORM\Entity
 */
final class UserPost
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned" = true})
     * @ORM\Id
     */
    private $user_id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned" = true})
     * @ORM\Id
     */
    private $post_id;

    /**
     * @var datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Constructor
     *
     * @param null|int $id
     */
    public function __construct(?array $id = [])
    {
        // This is a bad idea in real world, but we are just testing stuff here
        $this->user_id = $id['user_id'];
        $this->post_id = $id['post_id'];
        $this->createdAt = new DateTime();
    }

    /**
     * Get the date the record was created
     *
     * @return datetime
     */
    public function getCreatedAt(): datetime
    {
        return $this->createdAt;
    }
}
