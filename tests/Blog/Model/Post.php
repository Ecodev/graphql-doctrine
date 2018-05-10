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
 * @API\Sorting({"GraphQLTests\Doctrine\Blog\Sorting\UserName"})
 * @API\Filters({
 *     @API\Filter(field="custom", operator="GraphQLTests\Doctrine\Blog\Filtering\SearchOperatorType", type="string"),
 * })
 */
final class Post extends AbstractModel
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
    private $publicationDate;

    /**
     * @var string
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
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Returns the body
     *
     * @API\Field(name="content", description="The post content")
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set status
     *
     * @API\Input(type="GraphQLTests\Doctrine\Blog\Types\PostStatusType")
     *
     * @param string $status
     */
    public function setStatus(string $status = self::STATUS_PUBLIC): void
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @API\Field(type="GraphQLTests\Doctrine\Blog\Types\PostStatusType")
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set author of post
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Get author of post
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set date of publication
     */
    public function setPublicationDate(DateTime $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }

    /**
     * Get date of publication
     *
     * @return DateTime
     */
    public function getPublicationDate(): DateTime
    {
        return $this->publicationDate;
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
     *
     * @return bool
     */
    public function hasWords($words): bool
    {
        return count(array_diff($words, $this->getWords())) > 0;
    }

    public function isLong(int $wordLimit = 50): bool
    {
        return count($this->getWords()) > $wordLimit;
    }

    public function isAllowedEditing(User $user): bool
    {
        return $this->getUser() === $user;
    }

    /**
     * This should be silently ignored
     */
    public function setNothing(): void
    {
    }
}
