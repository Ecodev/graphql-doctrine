<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;

/**
 * A blog post with title and body
 *
 * @ORM\Entity
 */
class Post extends AbstractModel
{
    const STATUS_PRIVATE = 'private';
    const STATUS_PUBLIC = 'public';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, options={"default" = ""})
     */
    private $title = '';

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $body = '';

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $creationDate;

    /**
     * @var bool
     *
     * @ORM\Column(type="string", options={"default" = Post::STATUS_PRIVATE})
     */
    private $status = self::STATUS_PRIVATE;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="GraphQLTests\Doctrine\Blog\Model\User", inversedBy="posts")
     */
    private $user;

    /**
     * Set title
     *
     * @param string $title
     *
     * @return User
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the body
     *
     * @param string $body
     *
     * @return User
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Returns the body
     *
     * @API\Field(name="content", description="The post content")
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return User
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @API\Field(type="GraphQLTests\Doctrine\Blog\Types\PostStatusType")
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set user
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Get author of post
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set creation date
     * @param DateTime $creationDate
     */
    public function setCreationDate(DateTime $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    /**
     * Get date of creation
     * @return DateTime
     */
    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    /**
     * @return string[]
     */
    public function getWords(): array
    {
        return explode(' ', $this->getBody());
    }

    /**
     * @param string[] $words
     * @return bool
     */
    public function hasWords($words): bool
    {
        return count(array_diff($words, $this->getWords())) > 0;
    }
}
