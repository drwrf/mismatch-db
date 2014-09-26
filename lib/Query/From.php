<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB\Query;

use Mismatch\DB\Expression as e;
use UnexpectedValueException;

/**
 * Adds FROM functionality to a query builder.
 */
trait From
{
    /**
     * @var  array  The tables to interact with.
     */
    private $from = [];

    /**
     * Sets the table or tables to select data from.
     *
     * @param   mixed  $table
     * @return  self
     * @api
     */
    public function from($table)
    {
        $this->from = array_merge($this->from, (array) $table);

        return $this;
    }

    /**
     * Adds the FROM part to a query.
     *
     * @param  bool    $useAlias  Whether or not to alias the table
     * @param  string  $query
     */
    private function compileFrom($useAlias = true)
    {
        if (!$this->from) {
            throw new UnexpectedValueException(
                'Cannot compile FROM clause because there are no tables '.
                'to compile. Did you forget to call from()?');
        }

        $parts = [];

        foreach ($this->from as $source => $alias) {
            // Allow no aliasing as well, as denoted by an it key
            if (!is_int($source)) {
                $parts[] = $useAlias ? e\alias($source, $alias) : $source;
            } else {
                $parts[] = $alias;
            }
        }

        return implode($parts, ', ');
    }
}
