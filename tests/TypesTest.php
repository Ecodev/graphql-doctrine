<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use DateTime;
use Doctrine\ORM\Tools\SchemaValidator;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;
use GraphQLTests\Doctrine\Blog\Types\CustomType;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;
use stdClass;

class TypesTest extends \PHPUnit\Framework\TestCase
{
    use EntityManagerTrait;

    /**
     * @var Types
     */
    private $types;

    public function setUp(): void
    {
        $this->setUpEntityManager();

        $mapping = [
            DateTime::class => DateTimeType::class,
            stdClass::class => CustomType::class,
        ];
        $this->types = new Types($this->entityManager, $mapping);
    }

    public function testBlogMapping(): void
    {
        $validator = new SchemaValidator($this->entityManager);
        $errors = $validator->validateMapping();

        $this->assertEmpty($errors, 'doctrine annotations should be valid');
    }

    public function testGraphQLSchemaFromDocumentationMustBeValid(): void
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'query',
                'fields' => [
                    'posts' => [
                        'type' => Type::listOf($this->types->get(Post::class)), // Use automated ObjectType for output
                        'resolve' => function ($root, $args) {
                            // call to repository...
                        },
                    ],
                ],
                ]),
            'mutation' => new ObjectType([
                'name' => 'mutation',
                'fields' => [
                    'createPost' => [
                        'type' => Type::nonNull($this->types->get(Post::class)),
                        'args' => [
                            'input' => Type::nonNull($this->types->getInput(Post::class)), // Use automated InputObjectType for input
                        ],
                        'resolve' => function ($root, $args) {
                            // create new post and flush...
                        },
                    ],
                    'updatePost' => [
                        'type' => Type::nonNull($this->types->get(Post::class)),
                        'args' => [
                            'id' => Type::nonNull(Type::id()), // Use standard API when needed
                            'input' => $this->types->getInput(Post::class),
                        ],
                        'resolve' => function ($root, $args) {
                            // update existing post and flush...
                        },
                    ],
                ],
                ]),
        ]);

        $schema->assertValid();
        $this->assertTrue(true, 'passed validation successfully');
    }

    public function testCanGetUserDefinedScalarTypes(): void
    {
        $bool = $this->types->get(BooleanType::class);
        $status = $this->types->get(PostStatusType::class);

        $this->assertInstanceOf(BooleanType::class, $bool, 'must be a instance of bool');
        $this->assertInstanceOf(PostStatusType::class, $status, 'must be an instance of post status');

        $this->assertSame($bool, $this->types->get(BooleanType::class), 'must returns the same instance of bool');
        $this->assertSame($status, $this->types->get(PostStatusType::class), 'must returns the same instance of post status');
    }

    public function testCanGetUserMappedTypes(): void
    {
        $type = $this->types->get(stdClass::class);

        $this->assertInstanceOf(CustomType::class, $type, 'must be a instance of CustomType');
        $this->assertSame($type, $this->types->get('customName'));
    }

    public function testCanGetTypesWithBackslashPrefix(): void
    {
        $type = $this->types->get(stdClass::class);
        $this->assertSame($type, $this->types->get('\stdClass'));
    }

    public function testCanGetOutputTypes(): void
    {
        $userType = $this->types->get(User::class);
        $expected = [
            'name' => 'User',
            'description' => 'A blog author or visitor',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'String!',
                    'description' => 'The user real name',
                    'args' => [],
                ],
                [
                    'name' => 'email',
                    'type' => 'String',
                    'description' => 'The validated email or null',
                    'args' => [],
                ],
                [
                    'name' => 'isAdministrator',
                    'type' => 'Boolean!',
                    'description' => 'Whether the user is an administrator',
                    'args' => [],
                ],
                [
                    'name' => 'posts',
                    'type' => '[Post]!',
                    'description' => 'All posts of the specified status',
                    'args' => [
                        [
                            'name' => 'status',
                            'type' => 'PostStatus',
                            'description' => 'The status of posts as defined in \GraphQLTests\Doctrine\Blog\Model\Post',
                            'defaultValue' => 'public',
                        ],
                    ],
                ],
                [
                    'name' => 'postsWithIds',
                    'type' => '[Post]!',
                    'description' => null,
                    'args' => [
                        [
                            'name' => 'ids',
                            'type' => '[ID]!',
                            'description' => null,
                            'defaultValue' => null,
                        ],
                    ],
                ],
                [
                    'name' => 'id',
                    'type' => 'ID!',
                    'description' => null,
                    'args' => [],
                ],
            ],
        ];
        $this->assertObjectType($expected, $userType);
        $this->assertSame($userType, $this->types->get(User::class), 'must returns the same instance of user type');

        $postType = $this->types->get(Post::class);
        $expected = [
            'name' => 'Post',
            'description' => 'A blog post with title and body',
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'String!',
                    'description' => 'Title',
                    'args' => [],
                ],
                [
                    'name' => 'content',
                    'type' => 'String!',
                    'description' => 'The post content',
                    'args' => [],
                ],
                [
                    'name' => 'status',
                    'type' => 'PostStatus!',
                    'description' => 'Status',
                    'args' => [],
                ],
                [
                    'name' => 'user',
                    'type' => 'User!',
                    'description' => 'Author of post',
                    'args' => [],
                ],
                [
                    'name' => 'creationDate',
                    'type' => 'DateTime!',
                    'description' => 'Date of creation',
                    'args' => [],
                ],
                [
                    'name' => 'words',
                    'type' => '[String]!',
                    'description' => null,
                    'args' => [],
                ],
                [
                    'name' => 'hasWords',
                    'type' => 'Boolean!',
                    'description' => null,
                    'args' => [
                        [
                            'name' => 'words',
                            'type' => '[String]!',
                            'description' => null,
                            'defaultValue' => null,
                        ],
                    ],
                ],
                [
                    'name' => 'isLong',
                    'type' => 'Boolean!',
                    'description' => null,
                    'args' => [
                        [
                            'name' => 'wordLimit',
                            'type' => 'Int',
                            'description' => null,
                            'defaultValue' => 50,
                        ],
                    ],
                ],
                [
                    'name' => 'isAllowedEditing',
                    'type' => 'Boolean!',
                    'description' => null,
                    'args' => [
                        [
                            'name' => 'user',
                            'type' => 'UserID!',
                            'description' => null,
                            'defaultValue' => null,
                        ],
                    ],
                ],
                [
                    'name' => 'id',
                    'type' => 'ID!',
                    'description' => null,
                    'args' => [],
                ],
            ],
        ];
        $this->assertObjectType($expected, $postType);
        $this->assertSame($postType, $this->types->get(Post::class), 'must returns the same instance of post type');
    }

    public function testCanGetInputTypes(): void
    {
        $userType = $this->types->getInput(User::class);
        $expected = [
            'name' => 'UserInput',
            'description' => 'A blog author or visitor',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'String!',
                    'description' => 'Name',
                    'defaultValue' => null,
                ],
                [
                    'name' => 'email',
                    'type' => 'String',
                    'description' => 'A valid email or null',
                    'defaultValue' => null,
                ],
                [
                    'name' => 'password',
                    'type' => 'String!',
                    'description' => 'Encrypt and change the user password',
                    'defaultValue' => null,
                ],
            ],
        ];
        $this->assertInputType($expected, $userType);
        $this->assertSame($userType, $this->types->getInput(User::class), 'must returns the same instance of user type');

        $postType = $this->types->getInput(Post::class);
        $expected = [
            'name' => 'PostInput',
            'description' => 'A blog post with title and body',
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'String!',
                    'description' => 'Title',
                    'defaultValue' => null,
                ],
                [
                    'name' => 'body',
                    'type' => 'String!',
                    'description' => 'The body',
                    'defaultValue' => null,
                ],
                [
                    'name' => 'status',
                    'type' => 'PostStatus',
                    'description' => 'Status',
                    'defaultValue' => 'public',
                ],
                [
                    'name' => 'user',
                    'type' => 'UserID!',
                    'description' => 'Author of post',
                    'defaultValue' => null,
                ],
                [
                    'name' => 'creationDate',
                    'type' => 'DateTime!',
                    'description' => 'Date of creation',
                    'defaultValue' => null,
                ],
            ],
        ];
        $this->assertInputType($expected, $postType);
        $this->assertSame($postType, $this->types->getInput(Post::class), 'must returns the same instance of post type');
    }

    private function assertType(array $expected, Type $type, bool $assertArgs): void
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
                    $args[] = [
                        'name' => $arg->name,
                        'type' => $arg->getType()->toString(),
                        'description' => $arg->description,
                        'defaultValue' => $arg->defaultValue,
                    ];
                }
                $data['args'] = $args;
            } else {
                $data['defaultValue'] = $field->defaultValue;
            }

            $fields[] = $data;
        }

        $actual = [
            'name' => $type->name,
            'description' => $type->description,
            'fields' => $fields,
        ];
        //        var_export($actual);
        $this->assertEquals($expected, $actual);
    }

    private function assertInputType(array $expected, InputObjectType $type): void
    {
        $this->assertType($expected, $type, false);
    }

    private function assertObjectType(array $expected, ObjectType $type): void
    {
        $this->assertType($expected, $type, true);
    }

    public function testNonPublicGetterMustBeIgnored(): void
    {
        $actual = $this->types->get(Blog\Model\Special\IgnoredGetter::class);
        $expected = [
            'name' => 'IgnoredGetter',
            'description' => null,
            'fields' => [
                [
                    'name' => 'public',
                    'type' => 'String!',
                    'description' => null,
                    'args' => [],
                ],
                [
                    'name' => 'publicWithArgs',
                    'type' => '[String]!',
                    'description' => null,
                    'args' => [
                        [
                            'name' => 'arg1',
                            'type' => 'String!',
                            'description' => null,
                            'defaultValue' => null,
                        ],
                        [
                            'name' => 'arg2',
                            'type' => 'Int!',
                            'description' => null,
                            'defaultValue' => null,
                        ],
                        [
                            'name' => 'arg3',
                            'type' => '[String]',
                            'description' => null,
                            'defaultValue' => ['foo'],
                        ],
                    ],
                ],
                [
                    'name' => 'isValid',
                    'type' => 'Boolean!',
                    'description' => null,
                    'args' => [],
                ],
                [
                    'name' => 'hasMoney',
                    'type' => 'Boolean!',
                    'description' => null,
                    'args' => [],
                ],
                [
                    'name' => 'id',
                    'type' => 'ID!',
                    'description' => null,
                    'args' => [],
                ],
            ],
        ];

        $this->assertObjectType($expected, $actual);
    }

    public function testCanDeclareArrayOfEntity(): void
    {
        $actual = $this->types->get(Blog\Model\Special\ArrayOfEntity::class);
        $expected = [
            'name' => 'ArrayOfEntity',
            'description' => null,
            'fields' => [
                [
                    'name' => 'users',
                    'type' => '[User]!',
                    'description' => null,
                    'args' => [],
                ],
                [
                    'name' => 'id',
                    'type' => 'ID!',
                    'description' => null,
                    'args' => [],
                ],
            ],
        ];

        $this->assertObjectType($expected, $actual);
    }

    public function testFieldWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('Could not find type for method `GraphQLTests\Doctrine\Blog\Model\Special\NoType::getWithoutTypeHint()`. Either type hint the return value, or specify the type with `@API\Field` annotation.');
        $type = $this->types->get(Blog\Model\Special\NoType::class);
        $type->getFields();
    }

    public function testFieldReturningCollectionWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('The method `GraphQLTests\Doctrine\Blog\Model\Special\NoTypeCollection::getFoos()` is type hinted with a return type of `Doctrine\Common\Collections\Collection`, but the entity contained in that collection could not be automatically detected. Either fix the type hint, fix the doctrine mapping, or specify the type with `@API\Field` annotation.');
        $type = $this->types->get(Blog\Model\Special\NoTypeCollection::class);
        $type->getFields();
    }

    public function testCannotGetInvalidType(): void
    {
        $this->expectExceptionMessage('Given class name `DateTimeImmutable` is not a Doctrine entity. Either register a custom GraphQL type for `DateTimeImmutable` when instantiating `GraphQL\Doctrine\Types`, or change the usage of that class to something else.');
        $this->types->get(\DateTimeImmutable::class);
    }

    public function testArgumentWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('Could not find type for parameter `$bar` for method `GraphQLTests\Doctrine\Blog\Model\Special\NoTypeArgument::getFoo()`. Either type hint the parameter, or specify the type with `@API\Argument` annotation.');
        $type = $this->types->get(Blog\Model\Special\NoTypeArgument::class);
        $type->getFields();
    }

    public function testInputWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('Could not find type for parameter `$bar` for method `GraphQLTests\Doctrine\Blog\Model\Special\NoTypeInput::setFoo()`. Either type hint the parameter, or specify the type with `@API\Input` annotation.');
        $type = $this->types->getInput(Blog\Model\Special\NoTypeInput::class);
        $type->getFields();
    }

    public function testFieldWithExtraArgumentMustThrow(): void
    {
        $this->expectExceptionMessage('The following arguments were declared via `@API\Argument` annotation but do not match actual parameter names on method `GraphQLTests\Doctrine\Blog\Model\Special\ExtraArgument::getWithParams()`. Either rename or remove the annotations: misspelled_name');
        $type = $this->types->get(Blog\Model\Special\ExtraArgument::class);
        $type->getFields();
    }

    public function testFieldWithArrayArgumentMustThrow(): void
    {
        $this->expectExceptionMessage('The parameter `$arg1` on method `GraphQLTests\Doctrine\Blog\Model\Special\ArrayArgument::getWithParams()` is type hinted as `array` and is not overriden via `@API\Argument` annotation. Either change the type hint or specify the type with `@API\Argument` annotation.');
        $type = $this->types->get(Blog\Model\Special\ArrayArgument::class);
        $type->getFields();
    }

    public function testFieldWithObjectTypeArgumentMustThrow(): void
    {
        $this->expectExceptionMessage('Type for parameter `$user` for method `GraphQLTests\Doctrine\Blog\Model\Special\ObjectTypeArgument::getWithParams()` must be an instance of `GraphQL\Type\Definition\InputType`, but was `GraphQL\Type\Definition\ObjectType`. Use `@API\Argument` annotation to specify a custom InputType.');
        $type = $this->types->get(Blog\Model\Special\ObjectTypeArgument::class);
        $type->getFields();
    }
}
