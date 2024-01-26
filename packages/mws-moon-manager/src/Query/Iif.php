<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Query;

// https://stackoverflow.com/questions/22325898/using-collate-inside-doctrine-dql-query-symfony2
// Looks like someone turned your answer into a library 😄 github.com/beberlei/DoctrineExtensions/blob/master/src/Query/… 
// => But only for SQL, we need it for SQLIt for now...
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

// TODO : ok for mysql etc ... ?
class Iif extends FunctionNode
{
    public $expressionToTarget = null;
    public $ifTrue = null;
    public $ifFalse = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->expressionToTarget = $parser->ComparisonExpression();

        $parser->match(Lexer::T_COMMA);
        $this->ifTrue = $parser->ScalarExpression();

        $parser->match(Lexer::T_COMMA);
        $this->ifFalse = $parser->ScalarExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return sprintf(
            "IIF(%s,%s,%s)",
            $this->expressionToTarget->dispatch($sqlWalker),
            $this->ifTrue->dispatch($sqlWalker),
            $this->ifFalse->dispatch($sqlWalker),
        );
    }
}