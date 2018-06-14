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

use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Doctrine\DefaultFieldResolver;
use GraphQL\Doctrine\Types;
use Zend\ServiceManager\ServiceManager;

// Define custom types with a PSR-11 container
$customTypes = new ServiceManager([
    'invokables' => [
        DateTime::class => DateTimeType::class,
        'PostStatus' => PostStatusType::class,
    ],
]);

// Configure the type registry
$types = new Types($entityManager, $customTypes);

// Configure default field resolver to be able to use getters
GraphQL::setDefaultFieldResolver(new DefaultFieldResolver());

// Build your Schema
$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'query',
        'fields' => [
            'posts' => [
                'type' => Type::listOf($types->getOutput(Post::class)), // Use automated ObjectType for output
                'args' => [
                    [
                        'name' => 'filter',
                        'type' => $types->getFilter(Post::class), // Use automated filtering options
                    ],
                    [
                        'name' => 'sorting',
                        'type' => $types->getSorting(Post::class), // Use automated sorting options
                    ],
                ],
                'resolve' => function ($root, $args) use ($types): void {
                    $queryBuilder = $types->createFilteredQueryBuilder(Post::class, $args['filter'] ?? [], $args['sorting'] ?? []);

                // execute query...
                },
            ],
        ],
    ]),
    'mutation' => new ObjectType([
        'name' => 'mutation',
        'fields' => [
            'createPost' => [
                'type' => Type::nonNull($types->getOutput(Post::class)),
                'args' => [
                    'input' => Type::nonNull($types->getInput(Post::class)), // Use automated InputObjectType for input
                ],
                'resolve' => function ($root, $args): void {
                    // create new post and flush...
                },
            ],
            'updatePost' => [
                'type' => Type::nonNull($types->getOutput(Post::class)),
                'args' => [
                    'id' => Type::nonNull(Type::id()), // Use standard API when needed
                    'input' => $types->getPartialInput(Post::class),  // Use automated InputObjectType for partial input for updates
                ],
                'resolve' => function ($root, $args): void {
                    // update existing post and flush...
                },
            ],
        ],
    ]),
]);
```

## Usage

The public API is limited to the public methods on `Types` and the annotations.
So that's the constructor and:

- `$types->get()` to get custom types
- `$types->getOutput()` to get an `ObjectType` to be used in queries
- `$types->getFilter()` to get an `InputObjectType` to be used in queries
- `$types->getSorting()` to get an `InputObjectType` to be used in queries
- `$types->getInput()` to get an `InputObjectType` to be used in mutations (typically for creation)
- `$types->getPartialInput()` to get an `InputObjectType` to be used in mutations (typically for update)
- `$types->getId()` to get an `EntityIDType` which may be used to receive an
  object from database instead of a scalar
- `$types->has()` to check whether a type exists
- `$types->createFilteredQueryBuilder()` to be used in query resolvers

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
use GraphQL\Doctrine\Annotation as API;

/**
 * Returns the hashed password
 *
 * @API\Exclude
 *
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
use GraphQL\Doctrine\Annotation as API;

/**
 * Get status
 *
 * @API\Field(type="GraphQLTests\Doctrine\Blog\Types\PostStatusType")
 *
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
use GraphQL\Doctrine\Annotation as API;

/**
 * Returns all posts of the specified status
 *
 * @API\Field(args={@API\Argument(name="status", type="?GraphQLTests\Doctrine\Blog\Types\PostStatusType")})
 *
 * @param string $status the status of posts as defined in \GraphQLTests\Doctrine\Blog\Model\Post
 *
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
use GraphQL\Doctrine\Annotation as API;

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
as `DateTime`, or a custom class, then it will have to be configured beforehand.

The configuration is done with a [PSR-11 container](https://www.php-fig.org/psr/psr-11/)
implementation configured according to your needs. In the following example, we use
[zendframework/zend-servicemanager](https://github.com/zendframework/zend-servicemanager),
because it offers useful concepts such as: invokables, aliases, factories and abstract
factories. But any other PSR-11 container implementation could be used instead.


The keys should be the whatever you use to refer to the type in your model. Typically
that would be either the FQCN of a PHP class "native" type such as `DateTime`, or the
FQCN of a PHP class implementing the GraphQL type, or directly the GraphQL type name:

```php
$customTypes = new ServiceManager([
    'invokables' => [
        DateTime::class => DateTimeType::class,
        'PostStatus' => PostStatusType::class,
    ],
]);

$types = new Types($entityManager, $customTypes);

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

You may also get an input type for an entity by using `Types::getId()` to write
things like:

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

### Partial inputs

In addition to normal input types, it is possible to get a partial input type via
`getPartialInput()`. This is especially useful for mutations that update existing
entities and we do not want to have to re-submit all fields. By using a partial
input, the API client is able to submit only the fields that need to be updated
and nothing more.

This potentially reduces network traffic, because the client does not need
to fetch all fields just to be able re-submit them when he wants to modify only
one field.

And it also enable to easily design mass editing mutations where the client would
submit only a few fields to be updated for many entities at once. This could look like:

```php
<?php

$mutations = [
    'updatePosts' => [
        'type' => Type::nonNull(Type::listOf(Type::nonNull($types->get(Post::class)))),
        'args' => [
            'ids' => Type::nonNull(Type::listOf(Type::nonNull(Type::id()))),
            'input' => $types->getPartialInput(Post::class),  // Use automated InputObjectType for partial input for updates
        ],
        'resolve' => function ($root, $args) {
            // update existing posts and flush...
        }
    ],
];
```

### Default values

Default values are automatically detected from arguments for getters, as seen in
`getPosts()` example above.

For setters, the default value will be looked up on the mapped property, if there is
one matching the setter name. But if the setter itself has an argument with a default
value, it will take precedence.

So the following will make an input type with an optional field `name` with a
default value `jane`, an optional field `foo` with a default value `defaultFoo` and
a mandatory field `bar` without any default value:

```php
/**
 * @ORM\Column(type="string")
 */
private $name = 'jane';

public function setName(string $name = 'john'): void
{
    $this->name = $name;
}

public function setFoo(string $foo = 'defaultFoo'): void
{
    // do something
}

public function setBar(string $bar): void
{
    // do something
}
```

### Filtering and sorting

It is possible to expose generic filtering for entity fields and their types to let users easily
create and apply generic filters. This expose basic SQL-like syntax that should cover most simple
cases.

Filters are structured in an ordered list of groups. Each group contains an unordered set of joins
and conditions on fields. For simple case a single group of a few conditions would probably be enough.
But the ordered list of group allow more advanced filtering with `OR` logic between a set of conditions.

In the case of the `Post` class, it would generate [that GraphQL schema](tests/data/PostFilter.graphqls)
for filtering, and for sorting it would be [that simpler schema](tests/data/PostSorting.graphqls).

For concrete example of possibilities and variables syntax, refer to the
[test cases](tests/data/query-builder).

For security and complexity reasons, it is not meant to solve advanced use cases. For those it is
possible to write custom filters and sorting.

#### Custom filters

A custom filer must extend `AbstractOperator`.  This will allow to define custom arguments to for
the API, and then a method to build the DQL condition corresponding to the argument.

This would also allow to filter on joined relations by carefully adding joins when necessary.

Then custom filter might used like so:

```php
use GraphQL\Doctrine\Annotation as API;

/**
 * A blog post with title and body
 *
 * @ORM\Entity
 * @API\Filters({
 *     @API\Filter(field="custom", operator="GraphQLTests\Doctrine\Blog\Filtering\SearchOperatorType", type="string")
 * })
 */
final class Post extends AbstractModel
```

#### Custom sorting

A custom sorting option must implement `SortingInterface`. It has no arguments and must define
how to apply the sorting.

Similarly to custom filter, it may be possible to carefully add join if necessary.

Then custom sorting might used like so:

```php
use GraphQL\Doctrine\Annotation as API;

/**
 * A blog post with title and body
 *
 * @ORM\Entity
 * @API\Sorting({"GraphQLTests\Doctrine\Blog\Sorting\UserName"})
 */
final class Post extends AbstractModel
```
## Limitations

### Namespaces

The `use` statement is not supported. So types in annotation or doc blocks should
either be the FQCN or in the same namespace as the getter.

### Composite identifiers

Entities with composite identifiers are not supported for automatic creation of
input types. Possible workarounds are to change input argument to be something
else than an entity, write custom input types and use them via annotations, or
adapt the database schema.

### Logical operators in filtering

Logical operators support only two levels, and second level cannot mix logic operators. In SQL
that would means only one level of parentheses. So you can generate SQL that would look like:

```sql
-- mixed top level
WHERE cond1 AND cond2 OR cond3 AND ...

-- mixed top level and non-mixed sublevels
WHERE cond1 OR (cond2 OR cond3 OR ...) AND (cond4 AND cond5 AND ...) OR ...
```

But you **cannot** generate SQL that would like that:

```sql
-- mixed sublevels does NOT work
WHERE cond1 AND (cond2 OR cond3 AND cond4) AND ...

-- more than two levels will NOT work
WHERE cond1 OR (cond2 AND (cond3 OR cond4)) OR ...
```

Those cases would probably end up being too complex to handle on the client-side. And we recommend
instead to implement them as a custom filter on the server side, in order to hide complexity
from the client and benefit from Doctrine's QueryBuilder full flexibility.


### Sorting on join

Out of the box, it is not possible to sort by a field from a joined relation.
This should be done via a custom sorting to ensure that joins are done properly.

## Prior work

[Doctrine GraphQL Mapper](https://github.com/rahuljayaraman/doctrine-graphql) has
been an inspiration to write this package. While the goals are similar, the way
it works is different. In Doctrine GraphQL Mapper, annotations are spread between
properties and methods (and classes for filtering), but we work only on methods.
Setup seems slightly more complex, but might be more flexible. We built on conventions
and widespread use of PHP 7.1 type hinting to have an easier out-of-the-box experience.
