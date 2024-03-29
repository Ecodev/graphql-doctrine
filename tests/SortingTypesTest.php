<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\Special\ModelWithTraits;
use GraphQLTests\Doctrine\Blog\Model\User;
use PHPUnit\Framework\TestCase;

final class SortingTypesTest extends TestCase
{
    use TypesTrait;

    public function testCanGetPostSorting(): void
    {
        $actual = $this->types->getSorting(Post::class);
        $this->assertAllTypes('tests/data/PostSorting.graphqls', $actual);
    }

    public function testCanGetUserSorting(): void
    {
        $actual = $this->types->getSorting(User::class);
        $this->assertAllTypes('tests/data/UserSorting.graphqls', $actual);
    }

    public function testCanInheritSortingFromTraits(): void
    {
        $actual = $this->types->getSorting(ModelWithTraits::class);
        $this->assertAllTypes('tests/data/ModelWithTraitsSorting.graphqls', $actual);
    }
}
