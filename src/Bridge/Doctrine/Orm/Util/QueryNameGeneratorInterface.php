<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm\Util;

interface QueryNameGeneratorInterface
{
    /**
     * Generates a cacheable alias for DQL join.
     */
    public function generateJoinAlias(string $association): string;

    /**
     * Generates a cacheable parameter name for DQL query.
     */
    public function generateParameterName(string $name): string;
}
