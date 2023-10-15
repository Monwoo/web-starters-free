<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Query;

// https://stackoverflow.com/questions/22325898/using-collate-inside-doctrine-dql-query-symfony2
// Looks like someone turned your answer into a library 😄 github.com/beberlei/DoctrineExtensions/blob/master/src/Query/… 
// => But only for SQL, we need it for SQLIt for now...
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

class Replace extends FunctionNode
{
    public $expressionToTarget = null;
    public $replaceNeedle = null;
    public $replaceValue = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->expressionToTarget = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);
        $parser->match(Lexer::T_STRING);
        $lexer = $parser->getLexer();
        $this->replaceNeedle = $lexer->token['value'];

        $parser->match(Lexer::T_COMMA);
        $parser->match(Lexer::T_STRING);
        $lexer = $parser->getLexer();
        $this->replaceValue = $lexer->token['value'];

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return sprintf(
            "REPLACE(%s,'%s','%s')",
            $this->expressionToTarget->dispatch($sqlWalker),
            $this->replaceNeedle,
            $this->replaceValue,
        );
    }
}