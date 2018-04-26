<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use DateTime;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLTests\Doctrine\Blog\Types\CustomType;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;
use stdClass;
use Zend\ServiceManager\ServiceManager;

/**
 * Trait to easily set up types
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
        ]);

        $this->types = new Types($this->entityManager, $customTypes);
    }

    private function assertType(string $expectedFile, Type $type, bool $assertArgs): void
    {
        $fields = [];
        foreach ($type->getFields() as $field) {
            $data = [
                'name' => $field->name,
                'type' => $field->getType()->toString(),
                'description' => $field->description,
            ];

            if ($assertArgs) {
                $args = [];
                foreach ($field->args as $arg) {
                    $argData = [
                        'name' => $arg->name,
                        'type' => $arg->getType()->toString(),
                        'description' => $arg->description,

                    ];

                    if ($arg->defaultValueExists()) {
                        $argData['defaultValue'] = $arg->defaultValue;
                    }

                    $args[] = $argData;
                }
                $data['args'] = $args;
            } elseif ($field->defaultValueExists()) {
                $data['defaultValue'] = $field->defaultValue;
            }

            $fields[] = $data;
        }

        $actual = [
            'name' => $type->name,
            'description' => $type->description,
            'fields' => $fields,
        ];

        $expected = require $expectedFile;
        self::assertEquals($expected, $actual, 'Should equals expectation from: ' . $expectedFile);
    }

    private function assertInputType(string $expectedFile, InputObjectType $type): void
    {
        $this->assertType($expectedFile, $type, false);
    }

    private function assertObjectType(string $expectedFile, ObjectType $type): void
    {
        $this->assertType($expectedFile, $type, true);
    }
}
