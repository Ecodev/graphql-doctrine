<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an InputObjectType from a Doctrine entity to
 * specify joins.
 */
final class JoinOnTypeFactory extends AbstractTypeFactory
{
    public function __construct(
        Types $types,
        EntityManager $entityManager,
        private readonly FilterGroupJoinTypeFactory $filterGroupJoinTypeFactory,
        private readonly FilterGroupConditionTypeFactory $filterGroupConditionTypeFactory,
    ) {
        parent::__construct($types, $entityManager);
    }

    /**
     * Create an InputObjectType from a Doctrine entity to
     * specify joins.
     *
     * @param class-string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     */
    public function create(string $className, string $typeName): InputObjectType
    {
        $type = new InputObjectType([
            'name' => $typeName,
            'fields' => function () use ($className): array {
                $fields = [
                    [
                        'name' => 'type',
                        'type' => $this->types->get('JoinType'),
                        'defaultValue' => 'innerJoin',
                    ],
                    $this->filterGroupConditionTypeFactory->getField($className),
                ];

                // Only create join type, if there is anything to join on
                if ($this->filterGroupJoinTypeFactory->canCreate($className)) {
                    $fields[] = $this->filterGroupJoinTypeFactory->getField($className);
                }

                return $fields;
            },
        ]);

        return $type;
    }
}
