<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use ReflectionMethod;
use ReflectionParameter;

/**
 * A basic doc block reader to extract.
 */
final class DocBlockReader
{
    private readonly string $comment;

    public function __construct(ReflectionMethod $method)
    {
        $this->comment = $method->getDocComment() ?: '';
    }

    /**
     * Get the description of a method from the doc block.
     */
    public function getMethodDescription(): ?string
    {
        // Remove the comment markers
        $description = preg_replace('~\*/$~', '', $this->comment);
        $description = preg_replace('~^\s*(/\*\*|\* ?|\*/)~m', '', $description);

        // Keep everything before the first annotation
        $description = mb_trim(explode('@', $description ?? '')[0]);

        // Drop common "Get" or "Return" in front of comment
        $description = ucfirst(preg_replace('~^(set|get|return)s? ~i', '', $description) ?? '');

        return $description ?: null;
    }

    /**
     * Get the parameter description.
     */
    public function getParameterDescription(ReflectionParameter $param): ?string
    {
        $name = preg_quote($param->getName());

        if (preg_match('~@param\h+\H+\h+\$' . $name . '\h+(.*)~', $this->comment, $m)) {
            return ucfirst(mb_trim($m[1]));
        }

        return null;
    }

    /**
     * Get the parameter type.
     */
    public function getParameterType(ReflectionParameter $param): ?string
    {
        $name = preg_quote($param->getName());

        if (preg_match('~@param\h+(\H+)\h+\$' . $name . '(\h|\n)~', $this->comment, $m)) {
            return mb_trim($m[1]);
        }

        return null;
    }

    /**
     * Get the return type.
     */
    public function getReturnType(): ?string
    {
        if (preg_match('~@return\h+([^<]+<.*>|\H+)(\h|\n)~', $this->comment, $m)) {
            return mb_trim($m[1]);
        }

        return null;
    }
}
