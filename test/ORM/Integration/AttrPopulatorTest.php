<?php

namespace Mismatch\ORM\Integration;

class AttrPopulatorTest extends \PHPUnit_Framework_TestCase
{
    use IntegrationTestCase;

    public function setUp()
    {
        $this->createTable('Mismatch\ORM\Integration\AttrPopulator', [
            'primary_key' => ['type' => 'integer', 'primary' => true],
            'nullable'    => ['type' => 'integer', 'notnull' => false],
            'defaulted'   => ['type' => 'string', 'default' => 'default!'],
            'camel_case'  => ['type' => 'datetime'],
            'customized'  => ['type' => 'boolean'],
        ]);

        $this->attrs = AttrPopulator::metadata()['attrs'];
    }

    public function testPrimaryKey()
    {
        $subject = $this->attrs->get('primaryKey');

        $this->assertInstanceOf('Mismatch\Model\Attr\Primary', $subject);
        $this->assertEquals('primary_key', $subject->key);
    }

    public function testCamelCase()
    {
        $subject = $this->attrs->get('camelCase');

        $this->assertInstanceOf(
            'Doctrine\DBAL\Types\DatetimeType',
            $subject->type);

        $this->assertEquals('camel_case', $subject->key);
    }

    public function testDefaulted()
    {
        $this->assertEquals('default!', $this->attrs->get('defaulted')->default);
    }

    public function testNullable()
    {
        $this->assertTrue($this->attrs->get('nullable')->nullable);
    }

    public function testCustomized_notOverwritten()
    {
        $this->assertInstanceOf(
            'Doctrine\DBAL\Types\StringType',
            $this->attrs->get('customized')->type);
    }
}

class AttrPopulator extends IntegrationModel
{
    public static function init($m)
    {
        parent::init($m);

        $m->customized = 'string';
    }
}
