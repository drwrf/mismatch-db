<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Query;

use Mismatch\ORM\Expression\Composite;

/**
 * Adds HAVING functionality to a query builder.
 */
trait Having
{
    /**
     * @var  Composite  The expression used for building the HAVING clause
     */
    private $having;

    /**
     * Adds a set of AND HAVING filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     * @return self
     * @api
     */
    public function having($conds, array $binds = [])
    {
        $this->getHaving()->all($conds, $binds);

        return $this;
    }

    /**
     * Adds a set of OR HAVING filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     * @return self
     * @api
     */
    public function havingAny($conds, array $binds = [])
    {
        $this->getHaving()->any($conds, $binds);

        return $this;
    }

    /**
     * @return Composite
     */
    private function getHaving()
    {
        if (!$this->having) {
            $this->having = new Composite();
        }

        return $this->having;
    }

    /**
     * @return array
     */
    private function compileHaving()
    {
        if (!$this->having) {
            return null;
        }

        $expr = $this->having->getExpr($this->alias);
        $params = $this->having->getBinds();

        return [sprintf('HAVING %s', $expr), $params];
    }
}
