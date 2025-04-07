# GraphQL Doctrine

[![Build Status](https://github.com/ecodev/graphql-doctrine/workflows/main/badge.svg)](https://github.com/ecodev/graphql-doctrine/actions)
[![Code Quality](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Ecodev/graphql-doctrine/?branch=master)
[![Total Downloads](https://poser.pugx.org/ecodev/graphql-doctrine/downloads.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![Latest Stable Version](https://poser.pugx.org/ecodev/graphql-doctrine/v/stable.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![License](https://poser.pugx.org/ecodev/graphql-doctrine/license.png)](https://packagist.org/packages/ecodev/graphql-doctrine)
[![Join the chat at https://gitter.im/Ecodev/graphql-doctrine](https://badges.gitter.im/Ecodev/graphql-doctrine.svg)](https://gitter.im/Ecodev/graphql-doctrine)

A library to declare GraphQL types from Doctrine entities, PHP type hinting,
and attributes, and to be used with [webonyx/graphql-php](https://github.com/webonyx/graphql-php).

It reads most information from type hints, complete some things from existing
Doctrine attributes and allow further customizations with specialized attributes.
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
use Laminas\ServiceManager\ServiceManager;

// Define custom types with a PSR-11 container
$customTypes = new ServiceManager([
    'invokables' => [
        DateTimeImmutable::class => DateTimeType::class,
        'PostStatus' => PostStatusType::class,
    ],
    'aliases' => [
        'datetime_immutable' => DateTimeImmutable::class, // Declare alias for Doctrine type to be used for filters
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

The public API is limited to the public methods on `TypesInterface`, `Types`'s constructor, and the attributes.

Here is a quick overview of `TypesInterface`:

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
3. Attributes

That means it is always possible to override everything with attributes. But
existing type hints and dock blocks should cover the majority of cases.

### Exclude sensitive things

All getters, and setters, are included by default in the type. And all properties are included in the filters.
But it can be specified otherwise for each method and property.

To exclude a sensitive field from ever being exposed through the API, use `#[API\Exclude]`:

```php
use GraphQL\Doctrine\Attribute as API;

/**
 * Returns the hashed password
 *
 * @return string
 */
#[API\Exclude]
public function getPassword(): string
{
    return $this->password;
}
```

And to exclude a property from being exposed as a filter:

```php
use GraphQL\Doctrine\Attribute as API;

#[ORM\Column(name: 'password', type: 'string', length: 255)]
#[API\Exclude]
private string $password = '';
```

### Override output types

Even if a getter returns a PHP scalar type, such as `string`, it might be preferable
to override the type with a custom GraphQL type. This is typically useful for enum
or other validation purposes, such as email address. This is done by specifying the
GraphQL type FQCN via `#[API\Field]` attribute:

```php
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;

/**
 * Get status
 *
 * @return string
 */
#[API\Field(type: PostStatusType::class)]
public function getStatus(): string
{
    return $this->status;
}
```

### Type syntax

In most cases, the type must use the `::class` notation to specify the PHP class that is either implementing the GraphQL
type or the entity itself (see [limitations](#limitations)). Use string literals only if you must define it as nullable
and/or as an array. Never use the short name of an entity (it is only possible for user-defined custom types).

Supported syntaxes (PHP style or GraphQL style) are:

- `MyType::class`
- `'?Application\MyType'`
- `'null|Application\MyType'`
- `'Application\MyType|null'`
- `'Application\MyType[]'`
- `'?Application\MyType[]'`
- `'null|Application\MyType[]'`
- `'Application\MyType[]|null'`
- `'Collection<int, Application\MyType>'`

This attribute can be used to override other things, such as `name`, `description`
and `args`.

### Override arguments

Similarly to `#[API\Field]`, `#[API\Argument]` allows to override the type of argument
if the PHP type hint is not enough:

```php
use GraphQL\Doctrine\Attribute as API;

/**
 * Returns all posts of the specified status
 *
 * @param string $status the status of posts as defined in \GraphQLTests\Doctrine\Blog\Model\Post
 *
 * @return Collection<int, Post>
 */
public function getPosts(
     #[API\Argument(type: '?GraphQLTests\Doctrine\Blog\Types\PostStatusType')]
    ?string $status = Post::STATUS_PUBLIC
): Collection
{
    // ...
}
```

Once again, it also allows to override other things such as `name`, `description`
and `defaultValue`.

### Override input types

`#[API\Input]` is the opposite of `#[API\Field]` and can be used to override things for
input types (setters), typically for validations purpose. This would look like:

```php
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;

/**
 * Set status
 *
 * @param string $status
 */
#[API\Input(type: PostStatusType::class)]
public function setStatus(string $status = self::STATUS_PUBLIC): void
{
    $this->status = $status;
}
```

This attribute also supports `description`, and `defaultValue`.

### Override filter types

`#[API\FilterGroupCondition]` is the equivalent for filters that are generated from properties.
So usage would be like:

```php
use GraphQL\Doctrine\Attribute as API;

#[API\FilterGroupCondition(type: '?GraphQLTests\Doctrine\Blog\Types\PostStatusType')]
#[ORM\Column(type: 'string', options: ['default' => self::STATUS_PRIVATE])]
private string $status = self::STATUS_PRIVATE;
```

An important thing to note is that the value of the type specified will be directly used in DQL. That means
that if the value is not a PHP scalar, then it must be convertible to string via `__toString()`, or you have to
do the conversion yourself before passing the filter values to `Types::createFilteredQueryBuilder()`.

### Custom types

By default, all PHP scalar types and Doctrine collection are automatically detected
and mapped to a GraphQL type. However, if some getter return custom types, such
as `DateTimeImmutable`, or a custom class, then it will have to be configured beforehand.

The configuration is done with a [PSR-11 container](https://www.php-fig.org/psr/psr-11/)
implementation configured according to your needs. In the following example, we use
[laminas/laminas-servicemanager](https://github.com/laminas/laminas-servicemanager),
because it offers useful concepts such as: invokables, aliases, factories and abstract
factories. But any other PSR-11 container implementation could be used instead.

The keys should be the whatever you use to refer to the type in your model. Typically,
that would be either the FQCN of a PHP class "native" type such as `DateTimeImmutable`, or the
FQCN of a PHP class implementing the GraphQL type, or directly the GraphQL type name:

```php
$customTypes = new ServiceManager([
    'invokables' => [
        DateTimeImmutable::class => DateTimeType::class,
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
entities, when we do not want to have to re-submit all fields. By using a partial
input, the API client is able to submit only the fields that need to be updated
and nothing more.

This potentially reduces network traffic, because the client does not need
to fetch all fields just to be able re-submit them when he wants to modify only
one field.

And it also enables to easily design mass editing mutations where the client would
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
default value `john`, an optional field `foo` with a default value `defaultFoo` and
a mandatory field `bar` without any default value:

```php
#[ORM\Column(type: 'string']
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
and conditions on fields. For simple cases a single group of a few conditions would probably be enough.
But the ordered list of group allow more advanced filtering with `OR` logic between a set of conditions.

In the case of the `Post` class, it would generate [that GraphQL schema](tests/data/PostFilter.graphqls)
for filtering, and for sorting it would be [that simpler schema](tests/data/PostSorting.graphqls).

For concrete examples of possibilities and variables syntax, refer to the
[test cases](tests/data/query-builder).

For security and complexity reasons, it is not meant to solve advanced use cases. For those it is
possible to write custom filters and sorting.

#### Custom filters

A custom filer must extend `AbstractOperator`. This will allow to define custom arguments for
the API, and then a method to build the DQL condition corresponding to the argument.

This would also allow to filter on joined relations by carefully adding joins when necessary.

Then a custom filter might be used like so:

```php
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Filtering\SearchOperatorType;

/**
 * A blog post with title and body
 */
#[ORM\Entity]
#[API\Filter(field: 'custom', operator: SearchOperatorType::class, type: 'string')]
final class Post extends AbstractModel
```

#### Custom sorting

A custom sorting option must implement `SortingInterface`. The constructor has no arguments and
the `__invoke()` must define how to apply the sorting.

Similarly to custom filters, it may be possible to carefully add joins if necessary.

Then a custom sorting might be used like so:

```php
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Sorting\UserName;

/**
 * A blog post with title and body
 */
#[ORM\Entity]
#[API\Sorting([UserName::class])]
final class Post extends AbstractModel
```

## Limitations

### Namespaces

The `use` statement is not supported. So types in attributes or doc blocks must
be the FQCN, or the name of a user-defined custom types (but never the short name of an entity).

### Composite identifiers

Entities with composite identifiers are not supported for automatic creation of
input types. Possible workarounds are to change input argument to be something
else than an entity, write custom input types and use them via attributes, or
adapt the database schema.

### Logical operators in filtering

Logical operators support only two levels, and second level cannot mix logic operators. In SQL
that would mean only one level of parentheses. So you can generate SQL that would look like:

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
it works is different. In Doctrine GraphQL Mapper, attributes are spread between
properties and methods (and classes for filtering), but we work only on methods.
Setup seems slightly more complex, but might be more flexible. We built on conventions
and widespread use of PHP type hinting to have an easier out-of-the-box experience.
