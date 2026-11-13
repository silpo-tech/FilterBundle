<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm\Dql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * JSONB_CONTAINS(a, b) => (<a> @> <b>::jsonb).
 *
 * PostgreSQL only. Register it in the application:
 *
 *   doctrine:
 *     orm:
 *       dql:
 *         string_functions:
 *           JSONB_CONTAINS: FilterBundle\Bridge\Doctrine\Orm\Dql\JsonbContains
 */
class JsonbContains extends FunctionNode
{
    private mixed $firstArg = null;

    private mixed $secondArg = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->firstArg = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->secondArg = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            '(%s @> %s::jsonb)',
            $sqlWalker->walkArithmeticPrimary($this->firstArg),
            $sqlWalker->walkArithmeticPrimary($this->secondArg),
        );
    }
}
