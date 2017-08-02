# GraphQL Doctrine

[![Build Status](https://travis-ci.org/Ecodev/graphql-doctrine.svg?branch=master)](https://travis-ci.org/Ecodev/graphql-doctrine)
[![Code Quality](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/?branch=master)
[![Total Downloads](https://poser.pugx.org/ecodev/graphql-doctrine/downloads.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![Latest Stable Version](https://poser.pugx.org/ecodev/graphql-doctrine/v/stable.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![License](https://poser.pugx.org/ecodev/graphql-doctrine/license.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![Join the chat at https://gitter.im/Ecodev/graphql-doctrine](https://img.shields.io/badge/GITTER-join%20chat-green.svg)](https://gitter.im/Ecodev/graphql-doctrine)

A library to declare GraphQL types from Doctrine entities and annotations.

It reads most informations from type hints, complete some things from existing
Doctrine annotations and allow further customizations with specialized annotations.
It will then create [`ObjectType`](http://webonyx.github.io/graphql-php/type-system/object-types/#object-type-definition) instances with fields for all getter found on
Doctrine entities.

It will **not** build the entire schema. It is up to the user to use the automated
`ObjectType`, and other custom types, to define root queries.

## Quick start

Install the library via composer:

```sh
composer require ecodev/graphql-doctrine
```

And start using it:

```php
// Configure the type registry
$types = new Types($entityManager);

// Configure default field resolver to be able to use getters
GraphQL::setDefaultFieldResolver(new \GraphQL\Doctrine\DefaultFieldResolver());

// Build your Schema
$schema = new Schema([
    'Query' => [
        'fields' => [
            'users' => [
                'type' => $types->get(\Blog\Model\User::class),
                'resolve' => function ($root, $args) {
                    // call to repository...
                }
            ],
            'posts' => [
                'type' => $types->get(\Blog\Model\Post::class),
                'resolve' => function ($root, $args) {
                    // call to repository...
                }
            ],
        ],
    ],
    ]);
```

## Usage

### Exclude a field

All getters are included by default in the type. But it can be specified
otherwise for each getter. To exclude a sensitive field from ever being exposed
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

### Override scalar types

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

That annotation can be used to override other things, such as `name`, `description`
and `args`.

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

### Override arguments

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

## Prior work

[Doctrine GraphQL Mapper](https://github.com/rahuljayaraman/doctrine-graphql) has
been an inspiration to write this package. While the goals are similar, the way
it works is different. Annotations are spread between properties and getter, but
we work only on getter. Setup seems slighlty more complex, but might be more
flexible. We built on conventions to have an easier out-of-the-box experience.
