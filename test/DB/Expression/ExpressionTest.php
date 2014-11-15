<?php

namespace Mismatch\ORM\Expression;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function test_basicUsage()
    {
        $expr = new Expression('NOT (%s = ?)', ['test']);
        $this->assertEquals('NOT (col = ?)', $expr->getExpr('col'));
        $this->assertEquals(['test'], $expr->getBinds());
    }

    public function test_multipleColumns()
    {
        $expr = new Expression('NOT (%s = ? OR %s IS NULL)', ['test']);
        $this->assertEquals('NOT (col = ? OR col IS NULL)', $expr->getExpr('col'));
        $this->assertEquals(['test'], $expr->getBinds());
    }
}
