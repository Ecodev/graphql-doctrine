<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Attribute\Reader;

use GraphQL\Doctrine\Attribute\Argument;
use GraphQL\Doctrine\Attribute\Field;
use GraphQL\Doctrine\Attribute\Filter;
use GraphQL\Doctrine\Attribute\FilterGroupCondition;
use GraphQL\Doctrine\Attribute\Reader\Reader;
use GraphQL\Doctrine\Attribute\Sorting;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;
use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ReaderTest extends TestCase
{
    private Reader $reader;

    protected function setUp(): void
    {
        $this->reader = new Reader();
    }

    public function testGetRecursiveClassAttributesWithFilter(): void
    {
        $actual = $this->reader->getRecursiveClassAttributes(new ReflectionClass(Post::class), Filter::class);

        self::assertCount(2, $actual);
        self::assertCount(1, $actual[Post::class]);
        self::assertContainsOnlyInstancesOf(Filter::class, $actual[Post::class]);
        self::assertCount(1, $actual[AbstractModel::class]);
        self::assertContainsOnlyInstancesOf(Filter::class, $actual[AbstractModel::class]);
    }

    public function testGetRecursiveClassAttributesWithSorting(): void
    {
        $actual = $this->reader->getRecursiveClassAttributes(new ReflectionClass(Post::class), Sorting::class);

        self::assertCount(2, $actual);
        self::assertCount(2, $actual[Post::class]);
        self::assertContainsOnlyInstancesOf(Sorting::class, $actual[Post::class]);
        self::assertCount(1, $actual[AbstractModel::class]);
        self::assertContainsOnlyInstancesOf(Sorting::class, $actual[AbstractModel::class]);
    }

    public function testGetClassAttribute(): void
    {
        self::assertInstanceOf(
            Filter::class,
            $this->reader->getAttribute(new ReflectionClass(Post::class), Filter::class)
        );
    }

    public function testGetPropertyAttribute(): void
    {
        self::assertInstanceOf(
            FilterGroupCondition::class,
            $this->reader->getAttribute(new ReflectionProperty(Post::class, 'status'), FilterGroupCondition::class)
        );
    }

    public function testGetMethodAttribute(): void
    {
        self::assertInstanceOf(
            Field::class,
            $this->reader->getAttribute(new ReflectionMethod(Post::class, 'getBody'), Field::class)
        );
    }

    public function testGetParameterAttribute(): void
    {
        self::assertInstanceOf(
            Argument::class,
            $this->reader->getAttribute((new ReflectionMethod(User::class, 'getPosts'))->getParameters()[0], Argument::class)
        );
    }
}
