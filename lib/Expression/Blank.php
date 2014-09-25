<?php

namespace Mismatch\SQL\Expression;

class Blank extends Expression
{
    public function __construct()
    {
        parent::__construct('%s IS NULL OR NOT %s', []);
    }
}
