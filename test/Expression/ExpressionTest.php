<?php

namespace Mismatch\SQL\Expression;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function test_basicUsage()
    {
        $expr = new Expression('NOT (%s = ?)', ['test']);
        $this->assertEquals('NOT (col = ?)', $expr->getExpr('col'));
        $this->assertEquals(['test'], $expr->getBinds());
    }
}
