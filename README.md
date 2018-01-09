# GraphQL Doctrine

[![Build Status](https://travis-ci.org/Ecodev/graphql-doctrine.svg?branch=master)](https://travis-ci.org/Ecodev/graphql-doctrine)
[![Code Quality](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/?branch=master)
[![Total Downloads](https://poser.pugx.org/ecodev/graphql-doctrine/downloads.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![Latest Stable Version](https://poser.pugx.org/ecodev/graphql-doctrine/v/stable.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![License](https://poser.pugx.org/ecodev/graphql-doctrine/license.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![Join the chat at https://gitter.im/Ecodev/graphql-doctrine](https://badges.gitter.im/Ecodev/graphql-doctrine.svg)](https://gitter.im/Ecodev/graphql-doctrine)

A library to declare GraphQL types from Doctrine entities, PHP 7.1 type hinting,
and annotations, and to be used with [webonyx/graphql-php](https://github.com/webonyx/graphql-php).

It reads most information from type hints, complete some things from existing
Doctrine annotations and allow further customizations with specialized annotations.
It will then create [`ObjectType`](https://webonyx.github.io/graphql-php/type-system/object-types/#object-type-definition) and
 [`InputObjectType`](https://webonyx.github.io/graphql-php/type-system/input-types/#input-object-type)
instances with fields for all getter and setter respectively found on Doctrine entities.

It will **not** build the entire schema. It is up to the user to use automated
types, and other custom types, to define root queries.

## Quick start

Install the library via composer:

```sh
composer require ecodev/graphql-doctrine
```

And start using it:

```php
<?php

use Blog\Model\Post;
use Blog\Model\User;
use Blog\Type\DateTimeType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Doctrine\DefaultFieldResolver;
use GraphQL\Doctrine\Types;

// Define custom types mapping
$mapping = [
    \DateTime::class => DateTimeType::class,
];

// Configure the type registry
$types = new Types($entityManager, $mapping);

// Configure default field resolver to be able to use getters
GraphQL::setDefaultFieldResolver(new DefaultFieldResolver());

// Build your Schema
$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'query',
        'fields' => [
            'posts' => [
                'type' => Type::listOf($types->get(Post::class)), // Use automated ObjectType for output
                'resolve' => function ($root, $args) {
                    // call to repository...
                }
            ],
        ],
        ]),
    'mutation' => new ObjectType([
        'name' => 'mutation',
        'fields' => [
            'createPost' => [
                'type' => Type::nonNull($types->get(Post::class)),
                'args' => [
                    'input' => Type::nonNull($types->getInput(Post::class)), // Use automated InputObjectType for input
                ],
                'resolve' => function ($root, $args) {
                    // create new post and flush...
                }
            ],
            'updatePost' => [
                'type' => Type::nonNull($types->get(Post::class)),
                'args' => [
                    'id' => Type::nonNull(Type::id()), // Use standard API when needed
                    'input' => $types->getInput(Post::class),
                ],
                'resolve' => function ($root, $args) {
                    // update existing post and flush...
                }
            ],
        ],
        ]),
]);
```

## Usage

The public API is limited to the public methods on `Types` and the annotations.
So that's the constructor and:

- `$types->get()` to get either an `ObjectType` from an entity or any other
 custom types (eg: `string` or mapped type)
- `$types->getInput()` to get an `InputObjectType` to be used in mutations
- `$types->getId()` to get an `EntityIDType` which may be used to receive an
object from database instead of a scalar

### Information priority

To avoid code duplication as much as possible, information are gathered from
several places, where available. And each of those might be overridden. The order
of priority, from the least to most important is:

1. Type hinting
2. Doc blocks
3. Annotations

That means it is always possible to override everything with annotations. But
existing type hints and dock blocks should cover the majority of cases.

### Exclude a field

All getters, and setters, are included by default in the type. But it can be specified
otherwise for each method. To exclude a sensitive field from ever being exposed
through the API, use `@API\Exclude`:

```php
/**
 * Returns the hashed password
 *
 * @API\Exclude
 * @return string
 */
public function getPassword(): string
{
    return $this->password;
}
```

### Override output types

Even if a getter returns a PHP scalar type, such as `string`, it might be preferable
to override the type with a custom GraphQL type. This is typically useful for enum
or other validation purposes, such as email address. This is done by specifying the
GraphQL type FQCN via `@API\Field` annotation:

```php
/**
 * Get status
 *
 * @API\Field(type="GraphQLTests\Doctrine\Blog\Types\PostStatusType")
 * @return string
 */
public function getStatus(): string
{
    return $this->status;
}
```

The type must be the PHP class implementing the GraphQL type (see
[limitations](#limitations)). The declaration can be defined as nullable and/or as
an array with one the following syntaxes (PHP style or GraphQL style):

- `?MyType`
- `null|MyType`
- `MyType|null`
- `MyType[]`
- `?MyType[]`
- `null|MyType[]`
- `MyType[]|null`

This annotation can be used to override other things, such as `name`, `description`
and `args`.


#### Override arguments

Similarly to `@API\Field`, `@API\Argument` allows to override the type of argument
if the PHP type hint is not enough:

```php
/**
 * Returns all posts of the specified status
 * @API\Field(args={@API\Argument(name="status", type="?GraphQLTests\Doctrine\Blog\Types\PostStatusType")})
 * @param string $status the status of posts as defined in \GraphQLTests\Doctrine\Blog\Model\Post
 * @return Collection
 */
public function getPosts(?string $status = Post::STATUS_PUBLIC): Collection
{
    // ...
}
```

Once again, it also allows to override other things such as `name`, `description`
and `defaultValue`.

### Override input types

`@API\Input` is the opposite of `@API\Field` and can be used to override things for
input types (setters), typically for validations purpose. This would look like:

```php
/**
 * Set status
 *
 * @API\Input(type="GraphQLTests\Doctrine\Blog\Types\PostStatusType")
 *
 * @param string $status
 */
public function setStatus(string $status = self::STATUS_PUBLIC): void
{
    $this->status = $status;
}
```

This annotation also supports `name`, `description`, and `defaultValue`.

### Custom types

By default all PHP scalar types and Doctrine collection are automatically detected
and mapped to a GraphQL type. However if some getter return custom types, such
as `DateTime`, or a custom class, then it will have to be configured beforehand:

```php
$mapping = [
    DateTime::class => DateTimeType::class,
];

$types = new Types($entityManager, $mapping);

// Build schema...
```

That way it is not necessary to annotate every single getter returning one of the
configured type. It will be mapped automatically.

### Entities as input arguments

If a getter takes an entity as parameter, then a specialized `InputType` will
be created automatically to accept an `ID`. The entity will then be automatically
fetched from the database and forwarded to the getter. So this will work out of
the box:

```php
public function isAllowedEditing(User $user): bool
{
    return $this->getUser() === $user;
}
```

You may also get an input type for an entity by using `Types::getInput()`:

```php
// Custom InputType
$userInputType = $types->getInput(User::class);
```

And then use it like so in your resolver:

```php
[
    // ...
    'args' => [
        'id' => $types->getId(Post::class),
    ],
    'resolve' => function ($root, array $args) {
        $post = $args['id']->getEntity();

        // ...
    },
]
```

## Limitations

- The `use` statement is not supported. So types in annotation or doc blocks should
either be the FQCN or in the same namespace as the getter.

- Entities with composite identifiers are not supported for automatic creation of
input types. Possible workarounds are to change input argument to be something
else than an entity, write custom input types and use them via annotations, or
adapt the database schema.

## Prior work

[Doctrine GraphQL Mapper](https://github.com/rahuljayaraman/doctrine-graphql) has
been an inspiration to write this package. While the goals are similar, the way
it works is different. In Doctrine GraphQL Mapper, annotations are spread between
properties and methods, but we work only on methods. Setup seems slightly more complex,
but might be more flexible. We built on conventions and widespread use of PHP 7.1
type hinting to have an easier out-of-the-box experience.
