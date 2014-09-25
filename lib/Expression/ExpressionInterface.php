<?php

namespace Mismatch\SQL\Expression;

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
