<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB\Query;

use Mismatch\DB\Expression\Composite;

/**
 * Adds WHERE functionality to a query builder.
 */
trait Where
{
    /**
     * @var  Composite  The expression used for building the WHERE clause
     */
    private $where;

    /**
     * Adds a set of AND filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     * @return self
     * @api
     */
    public function where($conds, array $binds = [])
    {
        if (is_int($conds) || (is_array($conds) && is_int(key($conds)))) {
            $conds = [$this->pk => $conds];
        }

        $this->getWhere()->all($conds, $binds);

        return $this;
    }

    /**
     * Adds a set of OR filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     * @return self
     * @api
     */
    public function whereAny($conds, array $binds = [])
    {
        if (is_int($conds)) {
            $conds = [$this->pk => $conds];
        }

        $this->getWhere()->any($conds, $binds);

        return $this;
    }

    /**
     * @return Composite
     */
    private function getWhere()
    {
        if (!$this->where) {
            $this->where = (new Composite())->setAlias($this->alias);
        }

        return $this->where;
    }

    /**
     * @return array
     */
    private function compileWhere()
    {
        if (!$this->where) {
            return null;
        }

        $expr = $this->where->getExpr();
        $params = $this->where->getBinds();

        return [sprintf('WHERE %s', $expr), $params];
    }
}
