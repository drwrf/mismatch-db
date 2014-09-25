<?php

namespace Mismatch\DB\Expression;

use Mismatch\DB\Expression as Expr;

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

function not($value)
{
    return new Expr\Not($value);
}

function eq($value)
{
    return new Expr\Expression('%s = ?', [$value]);
}

function in($value)
{
    return new Expr\Expression('%s IN ?', [$value]);
}

function gt($value)
{
    return new Expr\Expression('%s > ?', [$value]);
}

function gte($value)
{
    return new Expr\Expression('%s >= ?', [$value]);
}

function after($value)
{
    return gt($value);
}

function lt($value)
{
    return new Expr\Expression('%s < ?', [$value]);
}

function lte($value)
{
    return new Expr\Expression('%s <= ?', [$value]);
}

function before($value)
{
    return lt($value);
}

function like($value)
{
    return new Expr\Expression('%s LIKE ?', [$value]);
}

function between($value1, $value2)
{
    return new Expr\Expression('%s BETWEEN ? AND ?', [$value1, $value2]);
}

function blank()
{
    return new Expr\Expression('%s IS NULL OR NOT %s', []);
}

function null()
{
    return new Expr\Expression('%s IS NULL', []);
}

function columnize($column, $source)
{
    if ($source && !strpos($column, '.') && !strpos($column, '(')) {
        return $source. '.' . $column;
    } else {
        return $column;
    }
}
