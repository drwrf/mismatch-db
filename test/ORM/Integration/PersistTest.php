<?php

namespace Mismatch\ORM\Integration;

use Exception;

class PersistTest extends \PHPUnit_Framework_TestCase
{
    use IntegrationTestCase;

    public function setUp()
    {
        parent::setUp();

        $this->createTable('Mismatch\ORM\Integration\PersistModel', [
            'id'   => ['type' => 'integer', 'primary' => true],
            'name' => ['type' => 'string'],
        ]);

        $this->seedTable('Mismatch\ORM\Integration\PersistModel', [
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ]);
    }

    public function testInsert_success()
    {
        $subject = new PersistModel([
            'name' => 'four'
        ]);

        $dataset = $subject->dataset();
        $subject->persist();

        $this->assertTrue($dataset->isPersisted());
        $this->assertEquals(4, $subject->id());
        $this->assertEquals(4, PersistModel::count());
    }

    public function testUpdate_success()
    {
        $subject = PersistModel::find(1);
        $subject->name = 'foo';

        $dataset = $subject->dataset();
        $subject->persist();

        $this->assertTrue($dataset->isPersisted());
        $this->assertEquals(1, $subject->id());
        $this->assertEquals(1, PersistModel::count(['name' => 'foo']));
    }
}

class PersistModel extends IntegrationModel
{
    public static function init($m)
    {
        parent::init($m);

        $m->id = 'primary';
        $m->name = 'string';
    }
}
