<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

/**
 * A factory to create unique but predictable names for aliases and parameters.
 *
 * Uniqueness is guaranteed within the same instance of factory only.
 */
final class UniqueNameFactory
{
    /**
     * @var int[]
     */
    private array $aliasCount = [];

    private int $parameterCount = 1;

    /**
     * Return a string to be used as parameter name in a query.
     */
    public function createParameterName(): string
    {
        return 'filter' . $this->parameterCount++;
    }

    /**
     * Return a string to be used as alias name in a query.
     */
    public function createAliasName(string $className): string
    {
        $alias = lcfirst(preg_replace('~^.*\\\~', '', $className) ?? '');
        if (!isset($this->aliasCount[$alias])) {
            $this->aliasCount[$alias] = 1;
        }

        return $alias . $this->aliasCount[$alias]++;
    }
}
