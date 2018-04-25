# RFC for filtering v3

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
LogicalOperator: ENUM(AND | OR)

PostFilter {
    title: PostFilteringFieldTitle
    body: PostFilteringFieldBody
    status: PostFilteringFieldStatus

    customFieldFilter {
        value: [String]!
        includeSubGroup: Boolean = false
    }
    where: LogicalOperator = AND
}

PostFilteringFieldTitle {
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
}

PostFilteringFieldStatus {
    greater {
        value: PostStatus!
    }
    lesser {
        value: PostStatus!
    }
    like {
        value: PostStatus!
    }
    equal {
        value: PostStatus!
    }
    in {
        values: [PostStatus]!
    }
}
```

```php
<?php

/**
 * @Api\Sorting("Application\Api\Sorting\CustomSortingField")
 * @Api\Filter(field="customFieldFilter", "Application\Api\Filter\CustomGroup")
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
                            name: {
                            equal: {
                                value: 'John',
                                }
                            }
                        }
                    ]
                },
            }
        },
        filters: [
            {
                title: {
                like: {
                    value: '%foo%',
                    }
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
                title: {
                like: {
                    value: '%foo%',
                    }
                }
            },
            {
                status: {
                equal: {
                    value: 'public',
                }
                }
            }
        ]
    },
}
```

Get posts created in 2016 (directly combining operators as compared to V2):

```typescript
const example3 = {
    query: {
        filters: [
            {
                dateCreation: {
                    greater: {
                        value: '2016-01-01T00:00:00Z',
                    },
                    lesser: {
                        value: '2017-01-01T00:00:00Z',
                    }
                }
            },
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
                dateCreation: {
                between: {
                    from: '2016-01-01T00:00:00Z',
                    to: '2017-01-01T00:00:00Z',
                    }
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
                dateCreation: {
                between: {
                    from: '2016-01-01T00:00:00Z',
                    to: '2017-01-01T00:00:00Z',
                }
            },
                title: {
                like: {
                    value: '%foo%',
                    },
                },
                where: 'OR',
            }
        ]
    },
}
```
