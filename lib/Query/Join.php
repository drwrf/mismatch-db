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
 * Adds JOIN functionality to a query builder.
 */
trait Join
{
    /**
     * @var  array  The tables to interact with.
     */
    private $join = [];

    /**
     * Adds a single JOIN statement to the query.
     *
     * If $join is an attribute that exists on the model, then
     * that attribute will be allowed to create the join.
     *
     * ```php
     * // INNER JOIN is added by default.
     * $query->join('authors a', ['a.id' => 'book.author_id']);
     *
     * // Although different types of joins can be specified.
     * $query->join('LEFT OUTER JOIN authors a', ['a.id' => 'book.author_id']);
     * ```
     *
     * @param  string  $table
     * @param  mixed   $conds
     * @return self
     * @api
     */
    public function join($table, $conds = [])
    {
        $this->join = array_merge($this->join, [$table => $conds]);

        return $this;
    }

    /**
     * Compiles the JOIN clause of a SQL query.
     *
     * @return  array
     */
    private function compileJoin()
    {
        if (!$this->join) {
            return null;
        }

        $parts = [];
        $params = [];

        foreach ($this->join as $table => $conds) {
            $sql = $table;

            // Allow an optional INNER JOIN specification, since it's so common
            if (false === strpos(strtoupper($sql), 'JOIN')) {
                $sql = 'INNER JOIN ' . $sql;
            }

            if ($on = $this->compileOn($conds)) {
                $sql .= sprintf(' ON (%s)', $on[0]);

                if ($on[1]) {
                    $params = array_merge($params, $on[1]);
                }
            }

            $parts[] = $sql;
        }

        return [implode($parts, ' '), $params];
    }

    /**
     * Compiles the ON clause of a JOIN.
     *
     * @param  mixed  $expr
     */
    private function compileOn($expr)
    {
        if (!$expr) {
            return;
        }

        if (!($expr instanceof Composite)) {
            $ret = new Composite();

            foreach ($expr as $owner => $related) {
                $ret->all([ sprintf('%s = %s', $owner, $related) ]);
            }

            $expr = $ret;
        }

        return [$expr->getExpr(), $expr->getBinds()];
    }
}
