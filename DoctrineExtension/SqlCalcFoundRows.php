<?php

namespace App\DoctrineExtension;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class SqlCalcFoundRows extends SqlWalker
{

    public function walkSelectClause($selectClause)
    {

        $sql = parent::walkSelectClause($selectClause);

        if ($this->getQuery()->getHint('mysqlWalker.sqlCalcFoundRows') === true) {
            if ($selectClause->isDistinct) {
                $sql = str_replace('SELECT DISTINCT', 'SELECT DISTINCT SQL_CALC_FOUND_ROWS', $sql);
            } else {
                $sql = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $sql);
            }
        }

        return $sql;
    }



}
