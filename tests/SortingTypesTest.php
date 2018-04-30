<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQLTests\Doctrine\Blog\Model\Post;

class SortingTypesTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait;

    public function testCanGetPostSorting(): void
    {
        $actual = $this->types->getSorting(Post::class);
        $this->assertAllTypes('tests/data/PostSorting.graphqls', $actual);
    }
}
