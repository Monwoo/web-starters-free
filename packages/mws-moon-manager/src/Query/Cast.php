<?php
// 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Query;

// https://www.sqlite.org/datatype3.html
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

class Cast extends FunctionNode
{
    public $expressionToTarget = null;
    public $toType = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->expressionToTarget = $parser->ScalarExpression();

        // $parser->match(Lexer::T_COMMA);
        $parser->match(Lexer::T_AS);
        $parser->match(Lexer::T_IDENTIFIER);
        $lexer = $parser->getLexer();
        $this->toType = $lexer->token['value'];

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return sprintf(
            "CAST(%s as %s)",
            $this->expressionToTarget->dispatch($sqlWalker),
            $this->toType,
        );
    }
}