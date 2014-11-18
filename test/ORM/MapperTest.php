<?php

namespace Mismatch\ORM;

use Mockery;

class MapperTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->metadata = Mockery::mock('Mismatch\Model\Metadata');
        $this->metadata->shouldReceive('getClass')
            ->andReturn('Mismatch\Mock\MappedModel');
    }

    public function testHydrate_populatesResult()
    {
        $subject = (new Mapper($this->metadata, []))->hydrate([
            'id' => 1,
            'name' => 'foo'
        ]);

        $this->assertEquals(1,     $subject->dataset->read('id'));
        $this->assertEquals('foo', $subject->dataset->read('name'));
        $this->assertTrue($subject->dataset->isPersisted());
    }

    public function testHydrate_deserializesAttrs()
    {
        $attrs = [
            'name' => Mockery::mock('Mismatch\Model\Attr\AttrInterface'),
        ];

        $attrs['name']->name = 'name';
        $attrs['name']->key = 'key';
        $attrs['name']->shouldReceive('deserialize')
            ->with(Mockery::type('Mismatch\Model\Dataset'), 'foo')
            ->andReturn('bar');

        $subject = (new Mapper($this->metadata, $attrs))->hydrate([
            'key' => 'foo',
        ]);

        $this->assertEquals('foo', $subject->dataset->read('key'));
        $this->assertEquals('bar', $subject->dataset->read('name'));
    }
}

namespace Mismatch\Mock;

use Mismatch;

class MappedModel
{
    public $dataset;

    public function __construct($dataset)
    {
        $this->dataset = $dataset;
    }
}
