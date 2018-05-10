<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Definition;

use Doctrine\Common\Annotations\AnnotationRegistry;
use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Language\AST\StringValueNode;
use GraphQLTests\Doctrine\Blog\Model\User;
use GraphQLTests\Doctrine\EntityManagerTrait;

final class EntityIDTypeTest extends \PHPUnit\Framework\TestCase
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
        $this->type = new EntityIDType($this->entityManager, User::class, 'UserID');
    }

    public function testMetadata(): void
    {
        self::assertSame('UserID', $this->type->name);
        self::assertSame('Automatically generated type to be used as input where an object of type `User` is needed', $this->type->description);
    }

    public function testCanGetIdWhenReadingVariable(): void
    {
        $actual = $this->type->parseValue('123')->getId();
        self::assertSame('123', $actual);
    }

    public function testCanGetEntityFromRepositoryWhenReadingVariable(): void
    {
        $actual = $this->type->parseValue('123')->getEntity();
        self::assertInstanceOf(User::class, $actual);
        self::assertSame(123, $actual->getId());
    }

    public function testNonExistingEntityThrowErrorWhenReadingVariable(): void
    {
        $this->expectExceptionMessage('Entity not found for class `GraphQLTests\Doctrine\Blog\Model\User` and ID `non-existing-id`');
        $this->type->parseValue('non-existing-id')->getEntity();
    }

    public function testCanGetIdWhenReadingLiteral(): void
    {
        $ast = new StringValueNode(['value' => '123']);
        $actual = $this->type->parseLiteral($ast)->getId();
        self::assertSame('123', $actual);
    }

    public function testCanGetEntityFromRepositoryWhenReadingLiteral(): void
    {
        $ast = new StringValueNode(['value' => '123']);
        $actual = $this->type->parseLiteral($ast)->getEntity();
        self::assertInstanceOf(User::class, $actual);
        self::assertSame(123, $actual->getId());
    }

    public function testNonExistingEntityThrowErrorWhenReadingLiteral(): void
    {
        $this->expectExceptionMessage('Entity not found for class `GraphQLTests\Doctrine\Blog\Model\User` and ID `non-existing-id`');
        $ast = new StringValueNode(['value' => 'non-existing-id']);
        $this->type->parseLiteral($ast)->getEntity();
    }

    public function testCanGetIdFromEntity(): void
    {
        $user = new User(456);

        $actual = $this->type->serialize($user);
        self::assertSame('456', $actual);
    }
}
