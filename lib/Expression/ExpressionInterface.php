<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB\Expression;

/**
 * Interface for all expressions.
 */
interface ExpressionInterface
{
    /**
     * Returns the expression provided by the comparator.
     *
     * @param   string  $column
     * @return  string
     */
    public function getExpr($column = null);

    /**
     * Returns the values that should be bound to the expression.
     *
     * @return  array
     */
    public function getBinds();
}
