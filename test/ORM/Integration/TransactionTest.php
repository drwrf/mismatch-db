<?php

namespace Mismatch\ORM\Integration;

use Exception;

class TransactionTestCase extends \PHPUnit_Framework_TestCase
{
    use IntegrationTestCase;

    public function setUp()
    {
        parent::setUp();

        $this->createTable('Mismatch\ORM\Integration\TransactionModel', [
            'id'   => ['type' => 'integer', 'primary' => true],
            'name' => ['type' => 'string'],
        ]);

        $this->seedTable('Mismatch\ORM\Integration\TransactionModel', [
            ['id' => 1, 'name' => 'test-1'],
            ['id' => 2, 'name' => 'test-2'],
        ]);
    }

    public function testTransactional_success()
    {
        $this->assertInstanceOf(
            'Mismatch\ORM\Integration\TransactionModel',
            TransactionModel::first(1));

        $result = TransactionModel::transactional(function($query) {
            return $query->delete(1);
        });

        $this->assertEquals(1, $result);
        $this->assertNull(TransactionModel::first(1));
    }

    public function testTransactional_failure()
    {
        $this->assertInstanceOf(
            'Mismatch\ORM\Integration\TransactionModel',
            TransactionModel::first(1));

        $exception = new Exception();

        try {
            TransactionModel::transactional(function($query) use ($exception) {
                $query->delete(1);
                throw $exception;
            });
            $this->fail('Transaction exception was not handled.');
        } catch (Exception $e) {
            $this->assertSame($exception, $e);
        }

        $this->assertInstanceOf(
            'Mismatch\ORM\Integration\TransactionModel',
            TransactionModel::first(1));
    }
}

class TransactionModel extends IntegrationModel
{
    public static function init($m)
    {
        parent::init($m);

        $m->id   = 'Primary';
        $m->name = 'String';
    }
}
