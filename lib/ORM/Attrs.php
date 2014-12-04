<?php

namespace Mismatch\ORM;

use Mismatch\Model\Attrs;

// Install native ORM types.
foreach ([
    'BelongsTo' => 'Mismatch\ORM\Attr\BelongsTo',
    'HasMany' => 'Mismatch\ORM\Attr\HasMany',
    'HasOne' => 'Mismatch\ORM\Attr\HasOne',
] as $name => $type) {
    Attrs::registerType($name, $type);
}

// Install doctrine-specific types
use Doctrine\DBAL\Types\Type;
use Mismatch\ORM\Attr\Native;

$types = Type::getTypesMap();

foreach ($types as $type => $class) {
    Attrs::registerType($type, function($type, $name, $opts) {
       $opts['type'] = Type::getType($type);

       return new Native($name, $opts);
    });
}
