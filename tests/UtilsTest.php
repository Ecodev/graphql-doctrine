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
     */
    public function testGetTypeName(string $input, string $expected): void
    {
        self::assertSame($expected, Utils::getTypeName($input));
    }
}
