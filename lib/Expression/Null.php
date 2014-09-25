<?php

namespace Mismatch\SQL\Expression;

class Null extends Expression
{
    public function __construct()
    {
        parent::__construct('%s IS NULL', []);
    }
}
