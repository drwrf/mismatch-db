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
}

namespace Mismatch\Mock;

use Mismatch;

class Orm
{
    use Mismatch\Model;
    use Mismatch\ORM;
}
