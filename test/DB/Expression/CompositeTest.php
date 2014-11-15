<?php

namespace Mismatch\ORM\Expression;

use Mismatch\ORM\Expression as e;

class CompositeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->subject = new Composite();
    }

    public function test_string()
    {
        $this->subject->all('name = ?', ['test'])
                      ->any('name is null');

        $expr = $this->subject->getExpr('test');
        $binds = $this->subject->getBinds();

        $this->assertEquals('name = ? OR name is null', $expr);
        $this->assertEquals(['test'], $binds);
    }

    public function test_arrayEq()
    {
        $this->subject->all(['name' => 'test' ])
                      ->any(['foo' => 'bar' ]);

        $expr = $this->subject->getExpr('test');
        $binds = $this->subject->getBinds();

        $this->assertEquals('test.name = ? OR test.foo = ?', $expr);
        $this->assertEquals(['test', 'bar'], $binds);
    }

    public function test_arrayIn()
    {
        $this->subject->all(['name' => ['test']])
                      ->any(['foo' => ['bar']]);

        $expr = $this->subject->getExpr('test');
        $binds = $this->subject->getBinds();

        $this->assertEquals('test.name IN ? OR test.foo IN ?', $expr);
        $this->assertEquals([['test'], ['bar']], $binds);
    }

    public function test_comparator()
    {
        $this->subject->all(['name' => e\eq('test')])
                      ->any(['foo' => e\eq('bar')]);

        $expr = $this->subject->getExpr('test');
        $binds = $this->subject->getBinds();

        $this->assertEquals('test.name = ? OR test.foo = ?', $expr);
        $this->assertEquals(['test', 'bar'], $binds);
    }
}
