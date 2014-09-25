<?php

namespace Mismatch\DB\Expression;

use Mismatch\DB\Expression as Expr;

/**
 * Returns a composite expression with each expression joined by AND.
 *
 * @param   string|array  $exprs
 * @param   array         $params
 * @return  Expr\Composite
 */
function all($exprs, array $params = [])
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
 */
function any($exprs, $params = [])
{
    return (new Expr\Composite())
        ->any($exprs, $params);
}

/**
 * Returns a bare expression, allowing you to specify its meaning.
 *
 * <code>
 * // As an example, here's how eq is implemented.
 * expr('%s = ?', ['foo']);
 * </code>
 *
 * @param   string  $expr
 * @param   array   $binds
 * @return  Expr\Expression
 */
function expr($expr, $binds = [])
{
    return new Expr\Expression($expr, $binds);
}

/**
 * Returns a NOT expression, negating any expressions you pass to it.
 *
 * @param   string|array|Expr\Expression  $value
 * @return  Expr\Not
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
 */
function like($value)
{
    return new Expr\Expression('%s LIKE ?', [$value]);
}

/**
 * Returns a BETWEEN expression.
 *
 * @param   mixed  $value
 * @return  Expr\Expression
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
 */
function null()
{
    return new Expr\Expression('%s IS NULL', []);
}

/**
 * @private
 */
function columnize($column, $source)
{
    if ($source && !strpos($column, '.') && !strpos($column, '(')) {
        return $source. '.' . $column;
    } else {
        return $column;
    }
}
