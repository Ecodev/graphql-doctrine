<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Definition;

use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Language\AST\StringValueNode;
use GraphQLTests\Doctrine\Blog\Model\User;
use GraphQLTests\Doctrine\EntityManagerTrait;

class EntityIDTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityManagerTrait;

    /**
     * @var EntityIDType
     */
    private $type;

    public function setUp(): void
    {
        $this->setUpEntityManager();
        $this->type = new EntityIDType($this->entityManager, User::class);
    }

    public function testMetadata(): void
    {
        self::assertSame('UserID', $this->type->name);
        self::assertSame('Automatically generated type to be used as input where an object of type `User` is needed', $this->type->description);
    }

    public function testCanGetEntityFromRepositoryWhenReadingVariable(): void
    {
        $actual = $this->type->parseValue('123');
        self::assertInstanceOf(User::class, $actual);
        self::assertSame(123, $actual->getId());
    }

    public function testCanGetEntityFromRepositoryWhenReadingLiteral(): void
    {
        $ast = new StringValueNode(['value' => '123']);
        $actual = $this->type->parseLiteral($ast);
        self::assertInstanceOf(User::class, $actual);
        self::assertSame(123, $actual->getId());
    }

    public function testCanGetIdFromEntity(): void
    {
        $user = new User(456);

        $actual = $this->type->serialize($user);
        self::assertSame('456', $actual);
    }
}
