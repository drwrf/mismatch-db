<?php

namespace Mismatch\ORM;

use Doctrine\DBAL\Types\Type;
use Mismatch\Model\Attrs;

class AttrsTest extends \PHPUnit_Framework_TestCase
{
    public function testDoctrineTypes()
    {
        $doctrineTypes = array_keys(Type::getTypesMap());
        $mismatchTypes = Attrs::availableTypes();

        $this->assertSame($doctrineTypes, array_intersect($doctrineTypes, $mismatchTypes));
    }
}
