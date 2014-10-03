<?php

namespace Mismatch\DB\Expression;

class Expression implements ExpressionInterface
{
    /**
     * @var  mixed  $expr
     */
    private $expr;

    /**
     * @var  mixed  $binds
     */
    private $binds;

    /**
     * @param  string  $expr
     * @param  array   $binds
     */
    public function __construct($expr, array $binds = [])
    {
        $this->expr = $expr;
        $this->binds = $binds;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpr($column = null)
    {
        return str_replace('%s', $column, $this->expr);
    }

    /**
     * {@inheritDoc}
     */
    public function getBinds()
    {
        return $this->binds;
    }
}
