<?php

namespace Mismatch\ORM\Integration;

use Exception;

class DestroyTest extends \PHPUnit_Framework_TestCase
{
    use IntegrationTestCase;

    public function setUp()
    {
        parent::setUp();

        $this->createTable('Mismatch\ORM\Integration\DestroyModel', [
            'id'   => ['type' => 'integer', 'primary' => true],
        ]);

        $this->seedTable('Mismatch\ORM\Integration\DestroyModel', [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);
    }

    public function testDestroy_success()
    {
        $subject = DestroyModel::find(1);
        $subject->destroy();

        $this->assertNull(DestroyModel::first(1));
    }

    /**
     * @expectedException  Mismatch\ORM\Exception\UndestroyableModelException
     */
    public function testDestroy_notPersisted()
    {
        $subject = new DestroyModel();
        $subject->destroy();
    }

    /**
     * @expectedException  Mismatch\ORM\Exception\UndestroyableModelException
     */
    public function testDestroy_nonExistent()
    {
        $subject = new DestroyModel([
            'id' => 999
        ]);

        $subject->dataset()
            ->markPersisted();

        $subject->destroy();
    }
}

class DestroyModel extends IntegrationModel
{
    public static function init($m)
    {
        parent::init($m);

        $m->id = 'Primary';
    }
}
