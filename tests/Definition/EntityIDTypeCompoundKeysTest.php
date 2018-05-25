<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Definition;

use Doctrine\Common\Annotations\AnnotationRegistry;
use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQLTests\Doctrine\Blog\Model\UserPost;
use GraphQLTests\Doctrine\EntityManagerTrait;

final class EntityIDTypeCompoundKeysTest extends \PHPUnit\Framework\TestCase
{
    use EntityManagerTrait;

    /**
     * @var EntityIDType
     */
    private $type;

    public function setUp(): void
    {
        AnnotationRegistry::registerLoader('class_exists');
        $this->setUpEntityManager();
        $this->type = new EntityIDType($this->entityManager, UserPost::class, 'UserID');
    }

    public function testCompoundPrimaryKeysAreNotSupported(): void
    {
        $this->expectExceptionMessage(
            'Entities with compound primary keys are not supported by EntityIDType. '
            . 'The entity `'
            . 'GraphQLTests\Doctrine\Blog\Model\UserPost'
            . "` cannot serialize it's id."
        );

        $userPost = new UserPost(['user_id' => 1, 'post_id' => 2]);
        $actual = $this->type->serialize($userPost);
    }
}
