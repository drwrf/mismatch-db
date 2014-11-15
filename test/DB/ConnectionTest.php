<?php

namespace Mismatch\ORM;

use Doctrine\DBAL\Types\Type;
use DateTime;
use PDO;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->subject = Connection::create([
            'driver' => 'pdo_sqlite',
            'memory' => 'true',
        ]);
    }

    public function test_create_pools()
    {
        $this->assertSame($this->subject, Connection::create([
            'driver' => 'pdo_sqlite',
            'memory' => 'true',
        ]));
    }

    public function test_prepareTypes()
    {
        $params = [
            1,
            true,
            false,
            null,
            [1],
            ['1'],
            new \DateTime(),
            'hi',
            new \StdClass(),
        ];

        $types = [
            Type::INTEGER,
            Type::BOOLEAN,
            Type::BOOLEAN,
            PDO::PARAM_NULL,
            Connection::PARAM_INT_ARRAY,
            Connection::PARAM_STR_ARRAY,
            Type::DATETIME,
            PDO::PARAM_STR,
            PDO::PARAM_STR,
        ];

        $this->assertSame($types, $this->subject->prepareTypes($params));
    }
}
