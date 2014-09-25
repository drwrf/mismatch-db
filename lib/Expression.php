<?php

namespace Mismatch\SQL\Expression;

use Mismatch\SQL\Expression as Expr;

function all($conds, $params = [])
{
    return new Expr\All($conds, $params);
}

function any($conds, $params = [])
{
    return new Expr\Any($conds, $params);
}

function expr($expr, $binds = [])
{
    return new Expr\Expression($expr, $binds);
}

function eq($value)
{
    return new Expr\Eq($value);
}

function notEq($value)
{
    return new Expr\NotEq($value);
}

function in($value)
{
    return new Expr\In($value);
}

function notIn($value)
{
    return new Expr\Not(new Expr\In($value));
}

function columnize($column, $source)
{
    if ($source && !strpos($column, '.') && !strpos($column, '(')) {
        return $source. '.' . $column;
    } else {
        return $column;
    }
}
