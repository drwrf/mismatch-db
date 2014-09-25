<?php

namespace Mismatch\SQL\Expression;

class All extends Composite
{
    /**
     * @param  mixed  $expr
     * @param  array  $binds
     */
    public function __construct($expr, array $binds = [])
    {
        $this->all($expr, $binds);
    }
}
