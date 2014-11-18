<?php

namespace Mismatch;

use Mismatch\Model\Metadata;

class ORMTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->subject = Metadata::get('Mismatch\Mock\Orm');
    }

    public function testTable()
    {
        $this->assertEquals('mismatch_mock_orms', $this->subject['orm:table']);
    }

    public function testFk()
    {
        $this->assertEquals('mismatch_mock_orm_id', $this->subject['orm:fk']);
    }

    public function testConnection()
    {
        $this->assertInstanceOf('Mismatch\ORM\Connection', $this->subject['orm:connection']);
    }

    public function testQuery()
    {
        $this->assertInstanceOf('Mismatch\ORM\Query', $this->subject['orm:query']);
    }

    public function testMapper()
    {
        $this->assertInstanceOf('Mismatch\ORM\Mapper', $this->subject['orm:mapper']);
    }
}

namespace Mismatch\Mock;

use Mismatch;

class Orm
{
    use Mismatch\Model;
    use Mismatch\ORM;

    public static function init($m)
    {
        $m['orm:credentials'] = [
            'driver' => 'pdo_sqlite'
        ];

        $m->id = 'Primary';
    }
}
