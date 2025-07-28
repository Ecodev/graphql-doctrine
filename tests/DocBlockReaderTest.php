<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\DocBlockReader;
use PHPUnit\Framework\Attributes\DataProvider;
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

    private const COMMENT_GENERIC = '
    /**
     * @return Collection<int, Foo>
     */';

    #[DataProvider('providerGetMethodDescription')]
    public function testGetMethodDescription(string|false $comment, ?string $expected): void
    {
        $reader = $this->create($comment);
        $actual = $reader->getMethodDescription();
        self::assertSame($expected, $actual);
    }

    public static function providerGetMethodDescription(): iterable
    {
        return [
            [false, null],
            [self::EMPTY_COMMENT, null],
            [self::COMMENT, 'Interesting data

Long description for the method
spanning lines'],
        ];
    }

    #[DataProvider('providerGetParameterDescription')]
    public function testGetParameterDescription(string|false $comment, string $parameterName, ?string $expected): void
    {
        $reader = $this->create($comment);
        $parameter = $this->createParameter($parameterName);
        $actual = $reader->getParameterDescription($parameter);
        self::assertSame($expected, $actual);
    }

    public static function providerGetParameterDescription(): iterable
    {
        return [
            [false, 'foo', null],
            [self::EMPTY_COMMENT, 'foo', null],
            [self::COMMENT, 'foo', 'Some param description'],
            'non-existing param' => [self::COMMENT, 'fo', null],
            [self::COMMENT, 'bar', null],
        ];
    }

    #[DataProvider('providerGetParameterType')]
    public function testGetParameterType(string|false $comment, string $parameterName, ?string $expected): void
    {
        $reader = $this->create($comment);
        $parameter = $this->createParameter($parameterName);
        $actual = $reader->getParameterType($parameter);
        self::assertSame($expected, $actual);
    }

    public static function providerGetParameterType(): iterable
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

    #[DataProvider('providerGetReturnType')]
    public function testGetReturnType(string|false $comment, ?string $expected): void
    {
        $reader = $this->create($comment);
        $actual = $reader->getReturnType();
        self::assertSame($expected, $actual);
    }

    public static function providerGetReturnType(): iterable
    {
        return [
            [false, null],
            [self::EMPTY_COMMENT, null],
            [self::COMMENT, 'bool'],
            [self::COMMENT_GENERIC, 'Collection<int, Foo>'],
        ];
    }

    private function create(string|false $comment): DocBlockReader
    {
        $method = new class($comment) extends ReflectionMethod {
            public function __construct(
                private readonly string|false $comment,
            ) {}

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
            public function __construct(
                public readonly string $mockedName,
            ) {}

            public function getName(): string
            {
                return $this->mockedName;
            }
        };
    }
}
