<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm\Util;

class QueryNameGenerator implements QueryNameGeneratorInterface
{
    private $incrementedAssociation = 1;
    private $incrementedName = 1;

    public function generateJoinAlias(string $association): string
    {
        return sprintf('%s_a%d', $association, $this->incrementedAssociation++);
    }

    public function generateParameterName(string $name): string
    {
        return sprintf('%s_p%d', str_replace('.', '_', $name), $this->incrementedName++);
    }
}
