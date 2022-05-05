<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation used to override values for a field argument in GraphQL.
 *
 * The name of the argument is required and must match the actual PHP argument name.
 *
 * All other values are optional and should only be used to override
 * what is declared by the original argument of the method.
 *
 * @Annotation
 * @NamedArgumentConstructor
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
    private const NO_VALUE_PASSED = '_hacky_no_value_past_find_a_better_solution_for_this_';

    public function __construct(
        string $name,
        ?string $type = null,
        ?string $description = null,
        mixed $defaultValue = self::NO_VALUE_PASSED
    ) {
        $settings = [
            'name' => $name,
            'type' => $type,
            'description' => $description,
        ];
        if ($defaultValue !== self::NO_VALUE_PASSED) {
            $settings['defaultValue'] = $defaultValue;
        }

        parent::__construct($settings);
    }
}
