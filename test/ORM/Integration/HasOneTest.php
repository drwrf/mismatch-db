<?php

namespace Mismatch\ORM\Integration;

use Mismatch\ORM\Exception\ModelNotFoundException;

class HasOneTest extends \PHPUnit_Framework_TestCase
{
    use IntegrationTestCase;

    public function setUp()
    {
        parent::setUp();

        $this->createTable('Mismatch\ORM\Integration\HasOneModel', [
            'id'        => ['type' => 'integer', 'primary' => true],
            'parent_id' => ['type' => 'integer', 'notnull' => false],
        ]);

        $this->seedTable('Mismatch\ORM\Integration\HasOneModel', [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 2],
            ['id' => 4, 'parent_id' => 3],
        ]);
    }

    public function test_read_successful()
    {
        $subject = HasOneModel::find(2);

        $this->assertEquals(3, $subject->child->id());
    }

    public function test_read_failed()
    {
        $subject = HasOneModel::find(4);

        try {
            $subject->child;
            $this->fail();
        } catch (ModelNotFoundException $e) {
            // Everything went well
        }
    }

    public function test_read_failed_butNullable()
    {
        $this->assertNull(HasOneModel::find(4)->nullChild);
    }

    public function test_read_newModel()
    {
        $subject = new HasOneModel();

        try {
            $subject->child;
            $this->fail();
        } catch (ModelNotFoundException $e) {
            // Everything went well
        }
    }

    public function test_read_newModel_butNullable()
    {
        $this->assertNull((new HasOneModel())->nullChild);
    }
}

class HasOneModel extends IntegrationModel
{
    public static function init($m)
    {
        parent::init($m);

        $m->id = 'Primary';

        $m->child = ['HasOne',
            'class' => get_called_class(),
            'fk' => 'parent_id'];

        $m->nullChild = ['HasOne?',
            'class' => get_called_class(),
            'fk' => 'parent_id',
            'pk' => 'id'];
    }
}
