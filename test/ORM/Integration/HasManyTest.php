<?php

namespace Mismatch\ORM\Integration;

class HasManyTest extends \PHPUnit_Framework_TestCase
{
    use IntegrationTestCase;

    public function setUp()
    {
        parent::setUp();

        $this->createTable('Mismatch\ORM\Integration\HasManyModel', [
            'id'        => ['type' => 'integer', 'primary' => true],
            'parent_id' => ['type' => 'integer', 'notnull' => false],
        ]);

        $this->seedTable('Mismatch\ORM\Integration\HasManyModel', [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 1],
            ['id' => 4, 'parent_id' => 2],
        ]);
    }

    public function test_read_successful()
    {
        $subject = HasManyModel::find(2);

        $this->assertInstanceOf('Mismatch\ORM\Query', $subject->children);
        $this->assertEquals(1, $subject->children->count());
        $this->assertEquals(4, $subject->children->first()->id);
    }
}

class HasManyModel extends IntegrationModel
{
    public static function init($m)
    {
        parent::init($m);

        $m->id = 'Primary';

        $m->children = ['HasMany',
            'class' => get_called_class(),
            'fk' => 'parent_id'];
    }
}
