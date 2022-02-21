<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;

/**
 * A blog author or visitor.
 *
 * @ORM\Entity(repositoryClass="GraphQLTests\Doctrine\Blog\Repository\UserRepository")
 */
final class User extends AbstractModel
{
    /**
     * @ORM\Column(name="custom_column_name", type="string", length=50, options={"default" = ""})
     */
    private string $name = '';

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $email = null;

    /**
     * @ORM\Column(name="password", type="string", length=255)
     * @API\Exclude
     */
    private string $password;

    /**
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    private bool $isAdministrator = false;

    /**
     * @var Collection<Post>
     *
     * @ORM\OneToMany(targetEntity="GraphQLTests\Doctrine\Blog\Model\Post", mappedBy="user")
     */
    private Collection $posts;

    /**
     * @var Collection<Post>
     *
     * @ORM\ManyToMany(targetEntity="GraphQLTests\Doctrine\Blog\Model\Post")
     */
    private Collection $favoritePosts;

    /**
     * @ORM\ManyToOne(targetEntity="GraphQLTests\Doctrine\Blog\Model\User")
     */
    private ?User $manager = null;

    /**
     * Constructor.
     */
    public function __construct(?int $id = null)
    {
        // This is a bad idea in real world, but we are just testing stuff here
        if ($id) {
            $this->id = $id;
        }

        $this->posts = new ArrayCollection();
    }

    /**
     * Set name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the user real name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set a valid email or null.
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get the validated email or null.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Encrypt and change the user password.
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT) ?: '';
    }

    /**
     * Returns the hashed password.
     *
     * @API\Exclude
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set whether the user is an administrator.
     *
     * @API\Exclude
     */
    public function setIsAdministrator(bool $isAdministrator): void
    {
        $this->isAdministrator = $isAdministrator;
    }

    /**
     * Get whether the user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->isAdministrator;
    }

    /**
     * Returns all posts of the specified status.
     *
     * @API\Field(args={@API\Argument(name="status", type="?GraphQLTests\Doctrine\Blog\Types\PostStatusType")})
     *
     * @param null|string $status the status of posts as defined in \GraphQLTests\Doctrine\Blog\Model\Post
     */
    public function getPosts(?string $status = Post::STATUS_PUBLIC): Collection
    {
        // Return unfiltered collection
        if ($status === null) {
            return $this->posts;
        }

        return $this->posts->filter(fn (Post $post) => $post->getStatus() === $status);
    }

    /**
     * @API\Field(type="GraphQLTests\Doctrine\Blog\Model\Post[]", args={@API\Argument(name="ids", type="id[]")})
     */
    public function getPostsWithIds(array $ids): Collection
    {
        return $this->posts->filter(fn (Post $post) => in_array($post->getId(), $ids, true));
    }

    public function setManager(?self $manager): void
    {
        $this->manager = $manager;
    }

    public function getManager(): ?self
    {
        return $this->manager;
    }
}
