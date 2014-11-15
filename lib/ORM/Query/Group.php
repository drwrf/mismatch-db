<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Query;

use Mismatch\ORM\Expression as e;

/**
 * Adds GROUP BY functionality to a query builder.
 */
trait Group
{
    /**
     * @var  array  The columns to group by
     */
    private $group = [];

    /**
     * Determines the columns to group by.
     *
     * @param  array  $columns
     * @return self
     * @api
     */
    public function group(array $columns)
    {
        $this->group = array_merge($this->group, $columns);

        return $this;
    }

    /**
     * Compiles the GROUP BY clause of a SQL query.
     *
     * @return  array
     */
    private function compileGroup()
    {
        if (!$this->group) {
            return null;
        }

        $parts = [];

        foreach ($this->group as $column) {
            $parts[] = e\columnize($column, $this->alias);
        }

        return 'GROUP BY ' . implode($parts, ', ');
    }
}
