<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;

/**
 * A blog author or visitor
 *
 * @ORM\Entity(repositoryClass="UserRepository")
 */
class User extends AbstractModel
{
    /**
     * @var string
     *
     * @ORM\Column(name="custom_column_name", type="string", length=50, options={"default" = ""})
     */
    private $name = '';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $email = null;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password = '';

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    private $isAdministrator = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="GraphQLTests\Doctrine\Blog\Model\Post", mappedBy="user")
     */
    private $posts;

    /**
     * Constructor
     */
    public function __construct(?int $id)
    {
        // This is a bad idea in real world, but we are just testing stuff here
        $this->id = $id;

        $this->posts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return User
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the user real name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get the validated email or null
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Encrypt and change the user password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Returns the hashed password
     *
     * @API\Exclude
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set isAdministrator
     *
     * @param bool $isAdministrator
     *
     * @return User
     */
    public function setIsAdministrator(bool $isAdministrator): void
    {
        $this->isAdministrator = $isAdministrator;
    }

    /**
     * Get whether the user is an administrator
     *
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return $this->isAdministrator;
    }

    /**
     * Returns all posts of the specified status
     * @API\Field(args={@API\Argument(name="status", type="?GraphQLTests\Doctrine\Blog\Types\PostStatusType")})
     * @param string $status the status of posts as defined in \GraphQLTests\Doctrine\Blog\Model\Post
     * @return Collection
     */
    public function getPosts(?string $status = Post::STATUS_PUBLIC): Collection
    {
        // Return unfiltered collection
        if ($status === null) {
            return $this->posts;
        }

        return $this->posts->filter(function (Post $post) use ($status) {
            return $post->getStatus() === $status;
        });
    }

    /**
     * @API\Field(type="GraphQLTests\Doctrine\Blog\Model\Post[]", args={@API\Argument(name="ids", type="id[]")})
     * @param array $ids
     */
    public function getPostsWithIds(array $ids): Collection
    {
        return $this->posts->filter(function (Post $post) use ($ids) {
            return in_array($post->getId(), $ids, true);
        });
    }
}
