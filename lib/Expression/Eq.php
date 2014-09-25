<?php

namespace Mismatch\SQL\Expression;

class Eq extends Expression
{
    public function __construct($value)
    {
        parent::__construct('%s = ?', [$value]);
    }
}
