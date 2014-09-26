<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB\Query;

use Mismatch\DB\Collection;
use Mismatch\DB\Expression as e;
use IteratorAggregate;
use Countable;
use DomainException;

/**
 * Handles SELECT-style queries.
 */
class Select extends Crud implements IteratorAggregate, Countable
{
    use From;
    use Join;
    use Where;
    use Having;
    use Order;
    use Group;
    use Limit;

    /**
     * @var  array  The columns to return
     */
    private $columns = [];

    /**
     * Attempts to find a single record.
     *
     * If no record is returned, then an exception is thrown.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @throws  DomainException
     * @return  mixed
     * @api
     */
    public function find($query = null, $conds = [])
    {
        $result = $this->first($query, $conds);

        if (!$result) {
            throw new DomainException(sprintf(
                'Could not find a single record using "%s".', $this));
        }

        return $result;
    }

    /**
     * Attempts to find a single record.
     *
     * If no record is returned, then null is returned.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  mixed
     * @api
     */
    public function first($query = null, $conds = [])
    {
        $result = $this->limit(1)->all($query, $conds);

        if ($result->valid()) {
            return $result->current();
        }
    }

    /**
     * Finds and returns a list of records limited by the
     * amount passed.
     *
     * @param   mixed  $limit
     * @return  Collection
     * @api
     */
    public function take($limit)
    {
        $this->limit($limit);

        return $this->all();
    }

    /**
     * Finds and returns all of the records.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  Collection
     * @api
     */
    public function all($query = null, $conds = [])
    {
        if ($query) {
            $this->where($query, $conds);
        }

        list($query, $params) = $this->compile();

        return $this->raw($query, $params);
    }

    /**
     * Returns the total number of records in the query.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  int
     * @api
     */
    public function count($query = null, $conds = [])
    {
        return $this->all()->count();
    }

    /**
     * Executes a raw query.
     *
     * @param   string  $query
     * @param   array   $params
     * @return  Collection
     * @api
     */
    public function raw($query, array $params = [])
    {
        $stmt = $this->conn->executeQuery($query, $params);

        // Wrap the statement in our own result type, so we have more
        // control over the interface that it exposes.
        return $this->prepareStatement($stmt);
    }

    /**
     * Chooses the columns to select in the result.
     *
     * ```php
     * // Aliases are supported as array keys
     * $query->columns(['column', 'column' => 'alias']);
     * ```
     *
     * @param   array  $columns
     * @return  self
     * @api
     */
    public function select(array $columns)
    {
        $this->columns = array_merge($this->columns, $columns);

        return $this;
    }

    /**
     * Implementation of IteratorAggregate
     *
     * @return  Iterator
     * @api
     */
    public function getIterator()
    {
        return $this->all();
    }

    /**
     * Hook to allow preparation of a SQL result just before
     * it's returned to the caller.
     *
     * @param  Doctrine\DBAL\Driver\Statement  $stmt
     * @api
     */
    protected function prepareStatement($stmt)
    {
        return new Collection($stmt);
    }

    /**
     * Compiles the query as a SELECT statement.
     *
     * @return  array
     */
    protected function compile()
    {
        if (!$this->columns) {
            $this->columns = ['*'];
        }

        $query[] = 'SELECT ' . $this->compileColumns();
        $query[] = 'FROM ' . $this->compileFrom();
        $params = [];

        if ($join = $this->compileJoin()) {
            $query[] = $join[0];
            $params = array_merge($params, $join[1]);
        }

        if ($expr = $this->compileWhere()) {
            $query[] = $expr[0];
            $params = array_merge($params, $expr[1]);
        }

        if ($group = $this->compileGroup()) {
            $query[] = $group;
        }

        if ($expr = $this->compileHaving()) {
            $query[] = $expr[0];
            $params = array_merge($params, $expr[1]);
        }

        if ($order = $this->compileOrder()) {
            $query[] = $order;
        }

        $query = implode(array_filter($query), ' ');
        $query = $this->compileLimit($query);

        return [$query, $params];
    }

    /**
     * Compiles the columns to select in a SELECT query.
     *
     * @return  array
     */
    private function compileColumns()
    {
        if (!$this->columns) {
            $this->columns = ['*'];
        }

        $parts = [];

        foreach ($this->columns as $column => $alias) {
            // Allow passing no alias, in which case the
            // alias is the actual column to get data from.
            if (is_int($column)) {
                $parts[] = e\columnize($alias, $this->alias);
            } else {
                $column = e\columnize($column, $this->alias);
                $parts[] = e\alias($column, $alias);
            }
        }

        return implode($parts, ', ');
    }
}
