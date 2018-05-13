<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Filtering;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;

final class ModuloOperatorType extends AbstractOperator
{
    protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'value',
                    'type' => Type::nonNull(Type::int()),
                ],
            ],
        ];
    }

    public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): ?string
    {
        $param = $uniqueNameFactory->createParameterName();
        $queryBuilder->setParameter($param, $args['value']);

        return 'MOD(' . $alias . '.' . $field . ', :' . $param . ') = 0';
    }
}
