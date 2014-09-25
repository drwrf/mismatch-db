<?php

namespace Mismatch\DB\Expression;

class Any extends Composite
{
    /**
     * @param  mixed  $expr
     * @param  array  $binds
     */
    public function __construct($expr, array $binds = [])
    {
        $this->any($expr, $binds);
    }
}
