# RFC for filtering v1

This is a RFC to support filtering features. It should let users easily create and apply generic
filters based on the entity fields and their types.

It should also be flexible enough to be able to add custom filters (for advanced DQL cases),
and custom sorting too.

## Schema

### Sorting

```graphql
PostSorting: [PostSortingPart!]

PostSortingPart {
    field: PostSortingField!
    order: SortingOrder = ASC
}

PostSortingField: ENUM(title | body | status | customSortingField ...)

SortingOrder: ENUM(ASC | DESC) = ASC
```

### Filtering

```graphql
PostQuery {
    joins {
        user {
            type: JoinType!
            query: [UserQuery!]
        }
    }
    filters: [PostFilter!]
}

JoinType: ENUM(innerJoin, leftJoin)
PostFilteringField: ENUM(title | body | status | customFilteringField ...)
LogicalOperator: ENUM(AND | OR)

PostFilter {
    field: PostFilteringField!
    type: ENUM(greater | lesser | like | equal | in | customGroup )
    value: String "true"
    values: [String]
    where: LogicalOperator = AND
}
```

```php
<?php

/**
 * @Api\Sorting("Application\Api\Sorting\CustomSortingField")
 * @Api\Filter("Application\Api\Filter\CustomGroup")
 */
class Post {
}
```
