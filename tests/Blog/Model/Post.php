<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Filtering\SearchOperatorType;
use GraphQLTests\Doctrine\Blog\Model\Special\NoInversedBy;
use GraphQLTests\Doctrine\Blog\Sorting\PostType;
use GraphQLTests\Doctrine\Blog\Sorting\UserName;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;

/**
 * A blog post with title and body.
 */
#[ORM\Entity]
#[API\Sorting(UserName::class)]
#[API\Sorting(PostType::class)]
#[API\Filter(field: 'custom', operator: SearchOperatorType::class, type: 'string')]
final class Post extends AbstractModel
{
    public const STATUS_PRIVATE = 'private';
    public const STATUS_PUBLIC = 'public';

    #[ORM\Column(type: 'string', length: 50, options: ['default' => ''])]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $body = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $publicationDate;

    #[API\FilterGroupCondition(type: '?GraphQLTests\Doctrine\Blog\Types\PostStatusType')]
    #[ORM\Column(type: 'string', options: ['default' => self::STATUS_PRIVATE])]
    private string $status = self::STATUS_PRIVATE;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: NoInversedBy::class)]
    private NoInversedBy $noInversedBy;

    /**
     * Set title.
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the body.
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Returns the body.
     */
    #[API\Field(name: 'content', description: 'The post content.')]
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set status.
     */
    #[API\Input(type: PostStatusType::class)]
    public function setStatus(string $status = self::STATUS_PUBLIC): void
    {
        $this->status = $status;
    }

    /**
     * Get status.
     */
    #[API\Field(type: PostStatusType::class)]
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set author of post.
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Get author of post.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set date of publication.
     */
    public function setPublicationDate(DateTimeImmutable $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }

    /**
     * Get date of publication.
     */
    public function getPublicationDate(): DateTimeImmutable
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
     * This should be silently ignored.
     */
    public function setNothing(): void
    {
    }
}
