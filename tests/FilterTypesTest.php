<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use Exception;
use GraphQL\Type\Definition\Type;
use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\Special\InvalidFilter;
use GraphQLTests\Doctrine\Blog\Model\Special\InvalidFilterGroupCondition;
use GraphQLTests\Doctrine\Blog\Model\Special\ModelWithTraits;
use PHPUnit\Framework\TestCase;

final class FilterTypesTest extends TestCase
{
    use TypesTrait;

    public function testCanGetPostFilter(): void
    {
        $actual = $this->types->getFilter(Post::class);

        $this->assertAllTypes('tests/data/PostFilter.graphqls', $actual);
    }

    public function testCanInheritFilterFromTraits(): void
    {
        $actual = $this->types->getFilter(ModelWithTraits::class);
        $this->assertAllTypes('tests/data/ModelWithTraitsFilter.graphqls', $actual);
    }

    public function providerFilteredQueryBuilder(): array
    {
        $values = [];
        $files = glob('tests/data/query-builder/*.php');

        if ($files === false) {
            throw new Exception('Cannot list files');
        }

        foreach ($files as $file) {
            $name = basename($file);
            $values[$name] = require $file;
        }

        return $values;
    }

    /**
     * @param class-string $className
     * @dataProvider providerFilteredQueryBuilder
     */
    public function testFilteredQueryBuilder(string $expected, string $className, array $filter, array $sorting): void
    {
        $queryBuilder = $this->types->createFilteredQueryBuilder($className, $filter, $sorting);
        $actual = $queryBuilder->getDQL();

        self::assertSame($expected, $actual);
        // @phpstan-ignore-next-line
        self::assertStringStartsWith('SELECT ', $queryBuilder->getQuery()->getSQL(), 'should be able to generate valid SQL without throwing exceptions');
    }

    public function testInvalidOperatorTypeMustThrow(): void
    {
        $type = $this->types->getFilter(InvalidFilter::class);
        $this->expectExceptionMessage('On class `GraphQLTests\Doctrine\Blog\Model\Special\InvalidFilter` the annotation `@API\Filter` expects a FQCN implementing `GraphQL\Doctrine\Definition\Operator\AbstractOperator`, but instead got: invalid_class_name');
        $this->getSchemaForType($type);
    }

    public function testGettingInvalidOperatorTypeMustThrow(): void
    {
        $this->expectExceptionMessage('Expects a FQCN implementing `GraphQL\Doctrine\Definition\Operator\AbstractOperator`, but instead got: invalid_class_name');
        $this->types->getOperator('invalid_class_name', Type::string());
    }

    public function testInvalidFilterGroupConditionTypeMustThrow(): void
    {
        $type = $this->types->getFilter(InvalidFilterGroupCondition::class);
        $this->expectExceptionMessage('On property `GraphQLTests\Doctrine\Blog\Model\Special\InvalidFilterGroupCondition::$foo` the annotation `@API\FilterGroupCondition` expects a, possibly wrapped, `GraphQL\Type\Definition\LeafType`, but instead got: GraphQL\Type\Definition\ObjectType');
        $this->getSchemaForType($type);
    }
}
