<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB\Query;

use Mismatch\DB\Expression as e;

/**
 * Adds ORDER BY functionality to a query builder.
 */
trait Order
{
    /**
     * @var  array  The columns to order by
     */
    private $order = [];

    /**
     * Determines the columns to order by.
     *
     * @param  array   $columns
     * @param  string  $dir
     * @return self
     * @api
     */
    public function order($columns, $dir = null)
    {
        if (!is_array($columns)) {
            $columns = [$columns => $dir];
        }

        $this->order = array_merge($this->order, $columns);

        return $this;
    }

    /**
     * Compiles the ORDER BY clause of a SQL query.
     *
     * @return  array
     */
    private function compileOrder()
    {
        if (!$this->order) {
            return null;
        }

        $parts = [];

        foreach ($this->order as $source => $dir) {
            $parts[] = e\columnize($source, $this->alias) . ' ' . strtoupper($dir);
        }

        return 'ORDER BY ' . implode($parts, ', ');
    }
}
