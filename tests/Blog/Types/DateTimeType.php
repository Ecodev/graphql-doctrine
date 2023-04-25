<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Types;

use DateTimeImmutable;
use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use UnexpectedValueException;

final class DateTimeType extends ScalarType
{
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:
        if (!($valueNode instanceof StringValueNode)) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, $valueNode);
        }

        return $valueNode->value;
    }

    public function parseValue(mixed $value): DateTimeImmutable
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException('Cannot represent value as DateTime date: ' . Utils::printSafe($value));
        }

        return new DateTimeImmutable($value);
    }

    public function serialize(mixed $value): mixed
    {
        if ($value instanceof DateTimeImmutable) {
            return $value->format('c');
        }

        return $value;
    }
}
