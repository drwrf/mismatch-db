<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Query;

use Mismatch\ORM\Expression as e;
use Mismatch\ORM\Expression\Composite;

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
        if ($this->isPk($conds)) {
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
        if ($this->isPk($conds)) {
            $conds = [$this->pk => $conds];
        }

        $this->getWhere()->any($conds, $binds);

        return $this;
    }

    /**
     * Adds a set of AND NOT filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     * @return self
     * @api
     */
    public function exclude($conds, array $binds = [])
    {
        if ($this->isPk($conds)) {
            $conds = [$this->pk => $conds];
        }

        $this->getWhere()->all(e\not(e\all($conds, $binds)));

        return $this;
    }

    /**
     * Adds a set of OR NOT filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     * @return self
     * @api
     */
    public function excludeAny($conds, array $binds = [])
    {
        if ($this->isPk($conds)) {
            $conds = [$this->pk => $conds];
        }

        $this->getWhere()->all(e\not(e\any($conds, $binds)));

        return $this;
    }

    /**
     * @return Composite
     */
    private function getWhere()
    {
        if (!$this->where) {
            $this->where = new Composite();
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

        $expr = $this->where->getExpr($this->alias);
        $params = $this->where->getBinds();

        return [sprintf('WHERE %s', $expr), $params];
    }

    /**
     * @param   mixed  $conds
     * @return  bool
     */
    private function isPk($conds)
    {
        return is_int($conds) || (is_array($conds) && is_int(key($conds)));
    }
}
