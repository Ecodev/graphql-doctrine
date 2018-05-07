<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\Utils;

final class UtilsTest extends \PHPUnit\Framework\TestCase
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
     * @param string $input
     * @param string $expected
     */
    public function testGetTypeName(string $input, string $expected): void
    {
        self::assertSame($expected, Utils::getTypeName($input));
    }
}
