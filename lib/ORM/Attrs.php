<?php

namespace Mismatch\Model;

// Install native ORM types.
foreach ([
    'BelongsTo' => 'Mismatch\ORM\Attr\BelongsTo',
] as $name => $type) {
    Attrs::registerType($name, $type);
}
