<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use DateTime;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use GraphQLTests\Doctrine\Blog\Types\CustomType;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;
use stdClass;
use Zend\ServiceManager\ServiceManager;

/**
 * Trait to easily set up types and assert them
 */
trait TypesTrait
{
    use EntityManagerTrait;

    /**
     * @var Types
     */
    private $types;

    public function setUp(): void
    {
        $this->setUpEntityManager();

        $customTypes = new ServiceManager([
            'invokables' => [
                BooleanType::class => BooleanType::class,
                DateTime::class => DateTimeType::class,
                stdClass::class => CustomType::class,
                'PostStatus' => PostStatusType::class,
            ],
            'aliases' => [
                'datetime' => DateTime::class, // Declare alias for Doctrine type to be used for filters
            ],
        ]);

        $this->types = new Types($this->entityManager, $customTypes);
    }

    private function assertType(string $expectedFile, Type $type): void
    {
        $actual = SchemaPrinter::printType($type) . PHP_EOL;
        self::assertStringEqualsFile($expectedFile, $actual, 'Should equals expectation from: ' . $expectedFile);
    }

    private function assertAllTypes(string $expectedFile, Type $type): void
    {
        $schema = $this->getSchemaForType($type);
        $actual = SchemaPrinter::doPrint($schema);

        self::assertStringEqualsFile($expectedFile, $actual, 'Should equals expectation from: ' . $expectedFile);
    }

    /**
     * Create a temporary schema for the given type
     *
     * @param Type $type
     *
     * @return Schema
     */
    private function getSchemaForType(Type $type): Schema
    {
        if ($type instanceof OutputType) {
            $config = [
                'query' => new ObjectType([
                    'name' => 'query',
                    'fields' => [
                        'defaultField' => $type,
                    ],
                ]),
            ];
        } else {
            $config = [
                'query' => new ObjectType([
                    'name' => 'query',
                ]),
                'mutation' => new ObjectType([
                    'name' => 'mutation',
                    'fields' => [
                        'defaultField' => $type,
                    ],
                ]),
            ];
        }

        return new Schema($config);
    }
}
