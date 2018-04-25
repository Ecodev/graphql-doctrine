# RFC for filtering v2

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
    greater {
        value: String!
    }
    lesser {
        value: String!
    }
    like {
        value: String!
    }
    equal {
        value: String!
    }
    in {
        values: [String]!
    }
    customGroup {
        value: [String]!
        includeSubGroup: Boolean = false
    }
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

## Usages

Get the most recent posts with a title containing 'foo' and the author name being exactly 'John':

```typescript
const example1 = {
    query: {
        joins: {
            user: {
                type: 'innerJoin',
                query: {
                    filters: [
                        {
                            field: 'name',
                            equal: {
                                value: 'John',
                            }
                        }
                    ]
                },
            }
        },
        filters: [
            {
                field: 'title',
                like: {
                    value: '%foo%',
                }
            }
        ]
    },
    sorting: [
        {
            field: "creationDate",
            order: "DESC",
        },
    ]
}
```

Get posts with a title containing 'foo' and that are public:

```typescript
const example2 = {
    query: {
        filters: [
            {
                field: 'title',
                like: {
                    value: '%foo%',
                }
            },
            {
                field: 'status',
                equal: {
                    value: 'public',
                }
            }
        ]
    },
}
```

Get posts created in 2016:

```typescript
const example3 = {
    query: {
        filters: [
            {
                field: 'dateCreation',
                greater: {
                    value: '2016-01-01T00:00:00Z',
                }
            },
            {
                field: 'dateCreation',
                lesser: {
                    value: '2017-01-01T00:00:00Z',
                }
            }
        ]
    },
}
```


Same but simpler, by combining operators on same field:

```typescript
const example4 = {
    query: {
        filters: [
            {
                field: 'dateCreation',
                greater: {
                    value: '2016-01-01T00:00:00Z',
                },
                lesser: {
                    value: '2017-01-01T00:00:00Z',
                }
            }
        ]
    },
}
```


Even simpler, by using more appropriate operator:

```typescript
const example5 = {
    query: {
        filters: [
            {
                field: 'dateCreation',
                between: {
                    from: '2016-01-01T00:00:00Z',
                    to: '2017-01-01T00:00:00Z',
                }
            }
        ]
    },
}
```

Get posts created in 2016 or containing 'foo':

```typescript
const example6 = {
    query: {
        filters: [
            {
                field: 'dateCreation',
                between: {
                    from: '2016-01-01T00:00:00Z',
                    to: '2017-01-01T00:00:00Z',
                }
            },
            {
                field: 'title',
                like: {
                    value: '%foo%',
                },
                where: 'OR',
            }
        ]
    },
}
```
