<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Type\Definition\Type;
use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\Special\InvalidFilter;
use GraphQLTests\Doctrine\Blog\Model\Special\ModelWithTraits;

final class FilterTypesTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait;

    public function testCanGetPostFilter(): void
    {
        $actual = $this->types->getFilter(Post::class);

        $this->assertAllTypes('tests/data/PostFilter.graphqls', $actual);
    }

    public function testCanInheritSortingFromTraits(): void
    {
        $actual = $this->types->getFilter(ModelWithTraits::class);
        $this->assertAllTypes('tests/data/ModelWithTraitsFilter.graphqls', $actual);
    }

    public function providerFilteredQueryBuilder(): array
    {
        $values = [];
        foreach (glob('tests/data/query-builder/*.php') as $file) {
            $name = basename($file);
            $values[$name] = require $file;
        }

        return $values;
    }

    /**
     * @dataProvider providerFilteredQueryBuilder
     *
     * @param string $expected
     * @param string $className
     * @param array $filter
     * @param array $sorting
     */
    public function testFilteredQueryBuilder(string $expected, string $className, array $filter, array $sorting): void
    {
        $queryBuilder = $this->types->createFilteredQueryBuilder($className, $filter, $sorting);
        $actual = $queryBuilder->getDQL();

        self::assertSame($expected, $actual);
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
}
