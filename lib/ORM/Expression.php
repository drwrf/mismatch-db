<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Expression;

use Mismatch\ORM\Expression as Expr;

/**
 * Returns a composite expression with each expression joined by AND.
 *
 * @param   string|array  $exprs
 * @param   array         $params
 * @return  Expr\Composite
 * @api
 */
function all($exprs, $params = null)
{
    return (new Expr\Composite())
        ->all($exprs, $params);
}

/**
 * Returns a composite expression with each expression joined by OR.
 *
 * @param   string|array  $exprs
 * @param   array         $params
 * @return  Expr\Composite
 * @api
 */
function any($exprs, $params = null)
{
    return (new Expr\Composite())
        ->any($exprs, $params);
}

/**
 * Returns a bare expression, allowing you to specify its meaning.
 *
 * ```php
 * // As an example, here's how eq is implemented.
 * expr('%s = ?', ['foo']);
 * ```
 *
 * @param   string  $expr
 * @param   array   $binds
 * @return  Expr\Expression
 * @api
 */
function expr($expr, $binds = null)
{
    return new Expr\Expression($expr, $binds);
}

/**
 * Returns a NOT expression, negating any expressions you pass to it.
 *
 * @param   string|array|Expr\Expression  $value
 * @return  Expr\Not
 * @api
 */
function not($value)
{
    return new Expr\Not($value);
}

/**
 * Returns an = expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function eq($value)
{
    return new Expr\Expression('%s = ?', [$value]);
}

/**
 * Returns an IN expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function in($value)
{
    return new Expr\Expression('%s IN ?', [$value]);
}

/**
 * Returns a > expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function gt($value)
{
    return new Expr\Expression('%s > ?', [$value]);
}

/**
 * Returns a >= expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function gte($value)
{
    return new Expr\Expression('%s >= ?', [$value]);
}

/**
 * Returns a > expression.
 *
 * This is useful for date comparisons, as it makes more semantic sense.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function after($value)
{
    return gt($value);
}

/**
 * Returns a < expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function lt($value)
{
    return new Expr\Expression('%s < ?', [$value]);
}

/**
 * Returns a <= expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function lte($value)
{
    return new Expr\Expression('%s <= ?', [$value]);
}

/**
 * Returns a < expression.
 *
 * This is useful for date comparisons, as it makes more semantic sense.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function before($value)
{
    return lt($value);
}

/**
 * Returns a LIKE expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function like($value)
{
    return new Expr\Expression('%s LIKE ?', [$value]);
}

/**
 * Returns a BETWEEN expression.
 *
 * @param   mixed  $value1
 * @param   mixed  $value2
 * @return  Expr\Expression
 * @api
 */
function between($value1, $value2)
{
    return new Expr\Expression('%s BETWEEN ? AND ?', [$value1, $value2]);
}

/**
 * Returns an expression that matches falsey values and NULL.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function blank()
{
    return new Expr\Expression('%s IS NULL OR NOT %s', []);
}


/**
 * Returns an expression that matches NULL values.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
 * @api
 */
function null()
{
    return new Expr\Expression('%s IS NULL', []);
}

/**
 * @ignore
 * @internal
 */
function columnize($column, $source)
{
    if ($source && !strpos($column, '.') && !strpos($column, '(')) {
        return $source. '.' . $column;
    } else {
        return $column;
    }
}

/**
 * @ignore
 * @internal
 */
function alias($source, $alias)
{
    if (is_string($alias) && $alias) {
        return sprintf("%s AS %s", $source, $alias);
    } else {
        return sprintf("%s", $source);
    }
}
