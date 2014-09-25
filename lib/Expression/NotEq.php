<?php

namespace Mismatch\SQL\Expression;

class NotEq extends Expression
{
    public function __construct($value)
    {
        parent::__construct('%s <> ?', [$value]);
    }
}
