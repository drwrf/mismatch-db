<?php

namespace Mismatch\Model;

// Install native ORM types.
foreach ([
    'BelongsTo' => 'Mismatch\ORM\Attr\BelongsTo',
    'HasMany' => 'Mismatch\ORM\Attr\HasMany',
] as $name => $type) {
    Attrs::registerType($name, $type);
}
