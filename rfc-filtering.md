# RFC for filtering v4

This is a RFC to support filtering features. It should let users easily create and apply generic
filters based on the entity fields and their types.

It should also be flexible enough to be able to add custom filters (for advanced DQL cases),
and custom sorting too.

This document use the class `Post` as an example.

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
PostFilter {
    joins {
        user {
            type: JoinType!
            filter: [UserFilter!]
        }
    }
    conditions: [PostCondition!]
}

JoinType: ENUM(innerJoin, leftJoin)
LogicalOperator: ENUM(AND | OR)


PostCondition {
    conditionLogic: LogicalOperator = AND
    fieldsLogic: LogicalOperator = AND
    fields {
        title: PostFilteringFieldTitle
        body: PostFilteringFieldBody
        status: PostFilteringFieldStatus
        customFieldFilter: PostFilteringFieldCustomFieldFilter
    }
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

PostFilteringFieldCustomFieldFilter {
    customGroup {
        value: [String]!
        includeSubGroup: Boolean = false
    }
}
```

## Declaration of custom filter and sorting

```php
<?php

/**
 * @Api\Sorting("Application\Api\Sorting\CustomSortingField")
 * @Api\Filter(field="customFieldFilter", "Application\Api\Filter\CustomGroup")
 */
class Post {
}
```

The PHP interface for those classes are still to be defined...

## Usages

### Example 1

Get the most recent posts with a title containing 'foo' and the author name being exactly 'John':

```typescript
const example1 = {
    filter: {
        joins: {
            user: {
                type: 'innerJoin',
                filter: {
                    conditions: [
                        {
                            fields: {
                                name: {
                                    equal: {
                                        value: 'John',
                                    },
                                },
                            },
                        },
                    ],
                },
            },
        },
        conditions: [
            {
                fields: {
                    title: {
                        like: {
                            value: '%foo%',
                        },
                    },
                },
            },
        ],
    },
    sorting: [
        {
            field: "creationDate",
            order: "DESC",
        },
    ],
}
```

### Example 2

Get posts with a title containing 'foo' and that are public:

```typescript
const example2 = {
    filter: {
        conditions: [
            {
                fields: {
                    title: {
                        like: {
                            value: '%foo%',
                        },
                    },
                    status: {
                        equal: {
                            value: 'public',
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 3

Get posts created in 2016 (directly combining operators as compared to V2):

```typescript
const example3 = {
    filter: {
        conditions: [
            {
                fields: {
                    dateCreation: {
                        greater: {
                            value: '2016-01-01T00:00:00Z',
                        },
                        lesser: {
                            value: '2017-01-01T00:00:00Z',
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 5

Even simpler, by using more appropriate operator:

```typescript
const example5 = {
    filter: {
        conditions: [
            {
                fields: {
                    dateCreation: {
                        between: {
                            from: '2016-01-01T00:00:00Z',
                            to: '2017-01-01T00:00:00Z',
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 6

Get posts created in 2016 or containing 'foo':

```typescript
const example6 = {
    filter: {
        conditions: [
            {
                fieldsLogic: 'OR',
                fields: {
                    dateCreation: {
                        between: {
                            from: '2016-01-01T00:00:00Z',
                            to: '2017-01-01T00:00:00Z',
                        },
                    },
                    title: {
                        like: {
                            value: '%foo%',
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 7

Get posts created in 2016 and containing 'foo', or else only containing 'bar':

```typescript
const example7 = {
    filter: {
        conditions: [
            {
                conditionLogic: 'OR', // top-level will be OR condition, this will have no effect, because it is the first condition, but keep it for demo purpose
                fieldsLogic: 'AND', // this is default value, but we explicitly set it for demo purpose
                fields: {
                    dateCreation: {
                        between: {
                            from: '2016-01-01T00:00:00Z',
                            to: '2017-01-01T00:00:00Z',
                        },
                    },
                    title: {
                        like: {
                            value: '%foo%',
                        },
                    },
                },
            },
            {
                conditionLogic: 'OR', // top-level will be OR condition
                fields: {
                    title: {
                        like: {
                            value: '%bar%',
                        },
                    },
                },
            },
        ],
    },
}
```

## Additional usages

This section reproduce examples from https://github.com/Ecodev/natural-search/issues/1.

### Example 8

Get users containing 'McClane'. This would use a custom filter `search` to handle
splitting search words and applying it on several fields at once (eg: `firstname`,
`lastname`, etc.).


```typescript
const naturalSearch1 = {
    filter: {
        conditions: [
            {
                fields: {
                    name: {
                        search: {
                            value: 'McClane',
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 9

Get users containing 'McClane' or 'foo'. That could leverage word splitting done on
server side in the custom `search` operator:

```typescript
const example9 = {
    filter: {
        conditions: [
            {
                fields: {
                    name: {
                        search: {
                            value: 'McClane foo',
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 10

Get users containing 'McClane' whose parents have ID 1:

```typescript
const example10 = {
    filter: {
        conditions: [
            {
                fields: {
                    name: {
                        search: {
                            value: 'McClane',
                        },
                    },
                    parents: {
                        contains: { // This would be a standard operator generating DQL `MEMBER OF`
                            values: ["1"],
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 11

Get users containing 'McClane' whose parents are either 1 or 2:

```typescript
const example11 = {
    filter: {
        conditions: [
            {
                fields: {
                    name: {
                        search: {
                            value: 'McClane',
                        },
                    },
                    parents: {
                        contains: { // This would be a standard operator generating DQL `MEMBER OF`
                            values: ["1", "2"],
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 12

Get users containing 'McClane' whose parents are either 1 or 2, or users containing 'Rambo' 
whose parents are 3 or 4:


```typescript
const example12 = {
    filter: {
        conditions: [
            {
                fields: {
                    name: {
                        search: {
                            value: 'McClane',
                        },
                    },
                    parents: {
                        contains: { // This would be a standard operator generating DQL `MEMBER OF`
                            values: ["1", "2"],
                        },
                    },
                },
            },
            {
                conditionLogic: 'OR',
                fields: {
                    name: {
                        search: {
                            value: 'Rambo',
                        },
                    },
                    parents: {
                        contains: { // This would be a standard operator generating DQL `MEMBER OF`
                            values: ["3", "4"],
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 13

Get users without parents:

```typescript
const example13 = {
    filter: {
        conditions: [
            {
                fields: {
                    parents: {
                        isEmpty: {
                            not: false, // this is required, because GraphQL expect to have a scalar as leaf
                        },
                    },
                },
            },
        ],
    },
}

```

### Example 14

Get users with any parents:

```typescript
const example14 = {
    filter: {
        conditions: [
            {
                fields: {
                    parents: {
                        isEmpty: {
                            not: true,
                        },
                    },
                },
            },
        ],
    },
}
```

### Example 15

Get things within a range:

```typescript
const example15 = {
    filter: {
        conditions: [
            {
                fields: {
                    size: {
                        between: {
                            to: 2,
                            from: 10,
                        },
                    },
                },
            },
        ],
    },
}
```

## Limitations

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