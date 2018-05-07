<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to override values for an field argument in GraphQL.
 *
 * The name of the argument is required and must match the actual PHP argument name.
 *
 * All other values are optional and should only be used to override
 * what is declared by the original argument of the method.
 *
 * @Annotation
 * @Target({"ANNOTATION"})
 * @Attributes({
 *     @Attribute("name", required=true, type="string"),
 *     @Attribute("type", required=false, type="string"),
 *     @Attribute("description", required=false, type="string"),
 *     @Attribute("defaultValue", required=false, type="mixed"),
 * })
 */
final class Argument extends AbstractAnnotation
{
}
