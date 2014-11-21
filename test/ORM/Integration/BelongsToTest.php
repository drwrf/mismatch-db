<?php

namespace Mismatch\ORM\Integration;

use Mismatch\ORM\Exception\ModelNotFoundException;

class BelongsToTest extends \PHPUnit_Framework_TestCase
{
    use IntegrationTestCase;

    public function setUp()
    {
        parent::setUp();

        $this->createTable('Mismatch\ORM\Integration\BelongsToModel', [
            'id'        => ['type' => 'integer', 'primary' => true],
            'parent_id' => ['type' => 'integer', 'notnull' => false],
        ]);

        $this->seedTable('Mismatch\ORM\Integration\BelongsToModel', [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 1],
            ['id' => 4, 'parent_id' => 2],
        ]);
    }

    public function test_read_successful()
    {
        $subject = BelongsToModel::find(2);

        $this->assertEquals(1, $subject->parent->id());
    }

    public function test_read_failed()
    {
        $subject = BelongsToModel::find(1);

        try {
            $subject->parent;
            $this->fail();
        } catch (ModelNotFoundException $e) {
            // Everything went well
        }
    }

    public function test_read_failed_butNullable()
    {
        $this->assertNull(BelongsToModel::find(1)->nullParent);
    }

    public function test_read_newModel()
    {
        $subject = new BelongsToModel();

        try {
            $subject->parent;
            $this->fail();
        } catch (ModelNotFoundException $e) {
            // Everything went well
        }
    }

    public function test_read_newModel_butNullable()
    {
        $this->assertNull((new BelongsToModel())->nullParent);
    }
}

class BelongsToModel extends IntegrationModel
{
    public static function init($m)
    {
        parent::init($m);

        $m->id = 'Primary';

        $m->parent = ['BelongsTo',
            'class' => get_called_class(),
            'fk' => 'parent_id'];

        $m->nullParent = ['BelongsTo?',
            'class' => get_called_class(),
            'fk' => 'parent_id',
            'pk' => 'id'];
    }
}
