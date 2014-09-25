<?php

namespace Mismatch\DB\Expression;

use Mismatch\DB\Expression as e;

class CompositeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->subject = new Composite();
        $this->subject->setAlias('test');
    }

    public function test_string()
    {
        $this->subject->all('name = ?', ['test'])
                      ->any('name is null');

        $expr = $this->subject->getExpr();
        $binds = $this->subject->getBinds();

        $this->assertEquals('name = ? OR name is null', $this->subject->getExpr());
        $this->assertEquals(['test'], $this->subject->getBinds());
    }

    public function test_arrayEq()
    {
        $this->subject->all(['name' => 'test' ])
                      ->any(['foo' => 'bar' ]);

        $expr = $this->subject->getExpr();
        $binds = $this->subject->getBinds();

        $this->assertEquals('test.name = ? OR test.foo = ?', $expr);
        $this->assertEquals(['test', 'bar'], $binds);
    }

    public function test_arrayIn()
    {
        $this->subject->all(['name' => ['test']])
                      ->any(['foo' => ['bar']]);

        $expr = $this->subject->getExpr();
        $binds = $this->subject->getBinds();

        $this->assertEquals('test.name IN ? OR test.foo IN ?', $expr);
        $this->assertEquals([['test'], ['bar']], $binds);
    }

    public function test_comparator()
    {
        $this->subject->all(['name' => e\eq('test')])
                      ->any(['foo' => e\eq('bar')]);

        $expr = $this->subject->getExpr();
        $binds = $this->subject->getBinds();

        $this->assertEquals('test.name = ? OR test.foo = ?', $expr);
        $this->assertEquals(['test', 'bar'], $binds);
    }
}
