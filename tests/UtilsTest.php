<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    public function providerGetTypeName(): array
    {
        return [
            ['\Blog\Model\Post', 'Post'],
            ['Blog\Model\Post', 'Post'],
            ['\Post', 'Post'],
            ['Post', 'Post'],
        ];
    }

    /**
     * @dataProvider providerGetTypeName
     *
     * @param class-string $className
     */
    public function testGetTypeName(string $className, string $expected): void
    {
        self::assertSame($expected, Utils::getTypeName($className));
    }
}
