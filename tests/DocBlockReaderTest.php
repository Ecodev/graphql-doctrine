<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\DocBlockReader;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionParameter;

class DocBlockReaderTest extends TestCase
{
    private const EMPTY_COMMENT = '
    /**
     */';

    private const COMMENT = '
    /**
     * Get interesting data
     *
     * Long description for the method
     * spanning lines
     *
     * @param null|string $foo Some param description
     * @param   User   $bar
     * @param \DateTimeImmutable $bazAbsolute
     * @param DateTimeImmutable $bazRelative
     * @return bool
     */';

    /**
     * @dataProvider providerGetMethodDescription
     */
    public function testGetMethodDescription(string|false $comment, ?string $expected): void
    {
        $reader = $this->create($comment);
        $actual = $reader->getMethodDescription();
        self::assertSame($expected, $actual);
    }

    public function providerGetMethodDescription(): array
    {
        return [
            [false, null],
            [self::EMPTY_COMMENT, null],
            [self::COMMENT, 'Interesting data

Long description for the method
spanning lines'],
        ];
    }

    /**
     * @dataProvider providerGetParameterDescription
     */
    public function testGetParameterDescription(string|false $comment, string $parameterName, ?string $expected): void
    {
        $reader = $this->create($comment);
        $parameter = $this->createParameter($parameterName);
        $actual = $reader->getParameterDescription($parameter);
        self::assertSame($expected, $actual);
    }

    public function providerGetParameterDescription(): array
    {
        return [
            [false, 'foo', null],
            [self::EMPTY_COMMENT, 'foo', null],
            [self::COMMENT, 'foo', 'Some param description'],
            'non-existing param' => [self::COMMENT, 'fo', null],
            [self::COMMENT, 'bar', null],
        ];
    }

    /**
     * @dataProvider providerGetParameterType
     */
    public function testGetParameterType(string|false $comment, string $parameterName, ?string $expected): void
    {
        $reader = $this->create($comment);
        $parameter = $this->createParameter($parameterName);
        $actual = $reader->getParameterType($parameter);
        self::assertSame($expected, $actual);
    }

    public function providerGetParameterType(): array
    {
        return [
            [false, 'foo', null],
            [self::EMPTY_COMMENT, 'foo', null],
            [self::COMMENT, 'foo', 'null|string'],
            'non-existing param' => [self::COMMENT, 'fo', null],
            [self::COMMENT, 'bar', 'User'],
            'do not make assumption on absolute types' => [self::COMMENT, 'bazAbsolute', '\DateTimeImmutable'],
            'do not make assumption on relative types' => [self::COMMENT, 'bazRelative', 'DateTimeImmutable'],
        ];
    }

    /**
     * @dataProvider providerGetReturnType
     */
    public function testGetReturnType(string|false $comment, ?string $expected): void
    {
        $reader = $this->create($comment);
        $actual = $reader->getReturnType();
        self::assertSame($expected, $actual);
    }

    public function providerGetReturnType(): array
    {
        return [
            [false, null],
            [self::EMPTY_COMMENT, null],
            [self::COMMENT, 'bool'],
        ];
    }

    private function create(string|false $comment): DocBlockReader
    {
        $method = new class($comment) extends ReflectionMethod {
            public function __construct(private readonly string|false $comment)
            {
            }

            public function getDocComment(): string|false
            {
                return $this->comment;
            }
        };

        return new DocBlockReader($method);
    }

    private function createParameter(string $name): ReflectionParameter
    {
        return new class($name) extends ReflectionParameter {
            public function __construct(public string $mockedName)
            {
            }

            public function getName(): string
            {
                return $this->mockedName;
            }
        };
    }
}
