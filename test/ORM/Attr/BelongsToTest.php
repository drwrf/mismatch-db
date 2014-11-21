<?php

namespace Mismatch\ORM\Attr;

use Mockery;
use StdClass;
use ArrayObject;

class BelongsToTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->owner = Mockery::mock('Mismatch\Model\Metadata');
        $this->relation = Mockery::mock('Mismatch\Model\Metadata');
    }

    public function test_isValid()
    {
        $this->relation
            ->shouldReceive('getClass')
            ->andReturn('StdClass');

        $this->assertTrue($this->createSubject()->isValid(new StdClass()));
        $this->assertFalse($this->createSubject()->isValid(new ArrayObject()));
        $this->assertFalse($this->createSubject()->isValid(null));
        $this->assertFalse($this->createSubject()->isValid(1));
        $this->assertFalse($this->createSubject()->isValid(''));
        $this->assertFalse($this->createSubject()->isValid([]));
    }

    public function test_pk_passed()
    {
        $subject = $this->createSubject('author', [
            'pk' => 'id',
        ]);

        $this->assertEquals('id', $subject->pk());
    }

    public function test_pk_null()
    {
        $subject = $this->createSubject('author');

        $this->assertEquals(null, $subject->pk());
    }

    public function test_fk_passed()
    {
        $subject = $this->createSubject('author', [
            'fk' => 'author_id'
        ]);

        $this->assertEquals('author_id', $subject->fk());
    }

    public function test_fk_null()
    {
        $subject = $this->createSubject('author');

        $this->assertEquals('author_id', $subject->fk());
    }

    public function test_key_passedViaKey()
    {
        $subject = $this->createSubject('author', [
            'key' => 'author_id'
        ]);

        $this->assertEquals('author_id', $subject->key);
    }

    public function test_key_passedViaFk()
    {
        $subject = $this->createSubject('author', [
            'fk' => 'author_id'
        ]);

        $this->assertEquals('author_id', $subject->key);
    }

    public function test_key_passedBoth()
    {
        $subject = $this->createSubject('author', [
            'key' => 'key',
            'fk' => 'fk'
        ]);

        $this->assertEquals('key', $subject->key);
        $this->assertEquals('fk', $subject->fk());
    }

    public function test_key_null()
    {
        $subject = $this->createSubject('author');

        $this->assertEquals('author_id', $subject->key);
    }

    protected function createSubject($name = 'foo', array $opts = [])
    {
        return new BelongsTo($name, array_merge([
            'metadata' => $this->owner,
            'each' => $this->relation,
        ], $opts));
    }
}
