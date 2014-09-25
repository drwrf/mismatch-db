<?php

namespace Mismatch\SQL\Expression;

class In extends Expression
{
    public function __construct($value)
    {
        parent::__construct('%s IN ?', [$value]);
    }
}
