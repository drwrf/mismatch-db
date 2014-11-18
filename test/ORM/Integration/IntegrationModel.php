<?php

namespace Mismatch\ORM\Integration;

use Mismatch;

class IntegrationModel
{
    use Mismatch\Model;
    use Mismatch\ORM;

    public static function init($m)
    {
        $m['orm:credentials'] = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
    }
}
