<?php

namespace Mismatch\ORM;

use Mismatch\Model\Attrs;

// Install native ORM types.
foreach ([
    'belongs-to' => 'Mismatch\ORM\Attr\BelongsTo',
    'has-many' => 'Mismatch\ORM\Attr\HasMany',
    'has-one' => 'Mismatch\ORM\Attr\HasOne',
] as $name => $type) {
    Attrs::registerType($name, $type);
}

// Install doctrine-specific types
use Doctrine\DBAL\Types\Type;
use Mismatch\ORM\Attr\Native;

$types = Type::getTypesMap();

foreach ($types as $type => $class) {
    Attrs::registerType($type, function($name, $opts) use ($type) {
       $opts['type'] = Type::getType($type);

       return new Native($name, $opts);
    });
}
