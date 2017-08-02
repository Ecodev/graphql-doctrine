<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\ORM\Tools\Setup;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\ObjectType;
use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;

class TypesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Types
     */
    private $types;

    public function setUp(): void
    {
        // Create the entity manager
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/Blog/Model'], true, null, null, false);
        $conn = ['url' => 'sqlite:///:memory:'];
        $this->entityManager = EntityManager::create($conn, $config);

        $mapping = [
            DateTime::class => DateTimeType::class,
        ];
        $this->types = new Types($this->entityManager, $mapping);
    }

    public function testBlogMapping(): void
    {
        $validator = new SchemaValidator($this->entityManager);
        $errors = $validator->validateMapping();

        $this->assertEmpty($errors, 'doctrine annotations should be valid');
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

    public function testCanGetEntityTypes(): void
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
                    'type' => '[Post]',
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

    private function assertObjectType(array $expected, ObjectType $type): void
    {
        $fields = [];
        foreach ($type->getFields() as $field) {
            $args = [];
            foreach ($field->args as $arg) {
                $args[] = [
                    'name' => $arg->name,
                    'type' => $arg->getType()->toString(),
                    'description' => $arg->description,
                    'defaultValue' => $arg->defaultValue,
                ];
            }

            $fields[] = [
                'name' => $field->name,
                'type' => $field->getType()->toString(),
                'description' => $field->description,
                'args' => $args,
            ];
        }

        $actual = [
            'name' => $type->name,
            'description' => $type->description,
            'fields' => $fields,
        ];
        //        var_export($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testCannotGetInvalidType(): void
    {
        $this->types->get(\stdClass::class);
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
                    'type' => 'String!',
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

    /**
     * @expectedException \GraphQL\Doctrine\Exception
     */
    public function testFieldWithoutTypeMustThrow(): void
    {
        $type = $this->types->get(Blog\Model\Special\NoType::class);
        $type->getFields();
    }

    /**
     * @expectedException \GraphQL\Doctrine\Exception
     */
    public function testFieldWithExtraArgumentMustThrow(): void
    {
        $type = $this->types->get(Blog\Model\Special\ExtraArgument::class);
        $type->getFields();
    }

    /**
     * @expectedException \GraphQL\Doctrine\Exception
     */
    public function testFieldReturningCollectionWithoutTypeMustThrow(): void
    {
        $type = $this->types->get(Blog\Model\Special\NoTypeCollection::class);
        $type->getFields();
    }
}
