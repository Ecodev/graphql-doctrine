<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Factory;

use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;
use PHPUnit\Framework\TestCase;

final class UniqueNameFactoryTest extends TestCase
{
    public function testCreateParameterName(): void
    {
        $factory1 = new UniqueNameFactory();
        $factory2 = new UniqueNameFactory();

        self::assertSame('filter1', $factory1->createParameterName());
        self::assertSame('filter1', $factory2->createParameterName());
        self::assertSame('filter2', $factory1->createParameterName());
        self::assertSame('filter2', $factory2->createParameterName());
    }

    public function testCreateAliasName(): void
    {
        $factory1 = new UniqueNameFactory();
        $factory2 = new UniqueNameFactory();

        self::assertSame('post1', $factory1->createAliasName(Post::class));
        self::assertSame('user1', $factory1->createAliasName(User::class));

        self::assertSame('post1', $factory2->createAliasName(Post::class));
        self::assertSame('user1', $factory2->createAliasName(User::class));
        self::assertSame('post2', $factory2->createAliasName(Post::class));
        self::assertSame('user2', $factory2->createAliasName(User::class));

        self::assertSame('post2', $factory1->createAliasName(Post::class));
        self::assertSame('user2', $factory1->createAliasName(User::class));
    }
}
