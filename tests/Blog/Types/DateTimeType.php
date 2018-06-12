<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Types;

use DateTime;
use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

final class DateTimeType extends ScalarType
{
    public function parseLiteral($valueNode, array $variables = null)
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:
        if (!($valueNode instanceof StringValueNode)) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }

        return $valueNode->value;
    }

    public function parseValue($value, array $variables = null)
    {
        if (!is_string($value)) {
            throw new \UnexpectedValueException('Cannot represent value as DateTime date: ' . Utils::printSafe($value));
        }

        return new DateTime($value);
    }

    public function serialize($value)
    {
        if ($value instanceof DateTime) {
            return $value->format('c');
        }

        return $value;
    }
}
