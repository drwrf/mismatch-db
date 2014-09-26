<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB\Query;

use Mismatch\DB\Connection;
use Closure;

/**
 * Base class for CRUD-like queries.
 */
abstract class Crud
{
    /**
     * @var  Connection  The connection to make requests against.
     */
    protected $conn;

    /**
     * @var  string  The alias to use for unadorned columns
     */
    protected $alias;

    /**
     * @var  string  The primary key to use for id shortcuts.
     */
    protected $pk;

    /**
     * Constructor.
     *
     * @param   Connection    $conn
     * @param   string|array  $table
     * @param   string        $pk
     */
    public function __construct($conn, $table, $pk = 'id')
    {
        $this->conn = $conn;
        $this->pk = $pk;

        // Set an alias that we can use for turning columns like "foo"
        // into something more specific like "alias.foo".
        $this->alias = is_array($table) ? current($table) : $table;

        // And set a default from based on the constructor.
        $this->from($table);
    }

    /**
     * Helpful aid for debugging.
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->compile()[0];
    }

    /**
     * Affords cloning queries.
     *
     * @see  http://php.net/manual/en/language.oop5.cloning.php
     */
    public function __clone()
    {
        // Nothing to do, just take it all!
    }

    /**
     * Sets the table or tables to select data from.
     *
     * @param   mixed  $table
     * @return  self
     * @api
     */
    abstract public function from($table);

    /**
     * Executes a raw query.
     *
     * @param   string  $query
     * @param   array   $params
     * @return  mixed
     * @api
     */
    abstract public function raw($query, array $params = []);

    /**
     * Compiles the query.
     *
     * @return  array
     */
    abstract protected function compile();
}
