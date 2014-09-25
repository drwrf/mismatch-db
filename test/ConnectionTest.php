<?php

namespace Mismatch\DB;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function test_create_pools()
    {
        $conn1 = Connection::create([
            'driver' => 'pdo_sqlite',
            'memory' => 'true',
        ]);

        $conn2 = Connection::create([
            'driver' => 'pdo_sqlite',
            'memory' => 'true',
        ]);

        $this->assertSame($conn1, $conn2);
    }
}
