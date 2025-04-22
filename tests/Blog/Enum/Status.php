<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Enum;

enum Status: string
{
    case New = 'new';
    case Active = 'active';
    case Archived = 'archived';
}
