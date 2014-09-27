<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB;

use Mismatch\DB\Expression as Expr;
use Mismatch\DB\Collection;
use IteratorAggregate;
use Countable;
use Closure;
use DomainException;

/**
 * Handles building an executing SQL queries.
 *
 * In conjunction with the factories provided by `Mismatch\DB\Expression`,
 * this becomes a powerful tool for building the queries common to CRUD
 * operations in web applications.
 *
 * As a quick example, let's find ten active authors who recently signed
 * up for our service, and let's order them by name.
 *
 * ```php
 * use Mismatch\DB\Query;
 * use Mismatch\DB\Expression as e;
 *
 * // We'll assume we've already created the connection elsewhere
 * $authors = (new Query($conn, 'authors'))
 *   ->order('name', 'asc')
 *   ->where([
 *     'signup' => e\after('2014-04-01')
 *     'active' => true,
 *   ]);
 *
 * foreach ($authors as $author) {
 *   // Do stuff!
 * }
 * ```
 *
 * ## Building queries
 *
 * After creating a new instance of your query class, you can start
 * chaining methods on to it to refine and filter results before they're
 * returned.
 *
 * ```php
 * // Both where and whereAny support complex expressions (outlined in
 * // the section below. Also, where is AND while whereAny is OR.
 * $query->where(['email' => 'rl.stine@example.com']);
 * $query->whereAny(['email' => 'ka.applegate@example.com']);
 *
 * // Both having and havingAny work exactly as where and whereAny.
 * $query->having(['sum' => e\gt(5)]);
 * $query->havingAny(['max' => e\lt(10)]);
 *
 * // Select specific columns. Aliases are supported as array keys
 * $query->columns(['column', 'column' => 'alias']);
 *
 * // INNER JOIN is added by default.
 * $query->join('authors a', ['a.id' => 'book.author_id']);
 *
 * // Although different types of joins can be specified.
 * $query->join('LEFT OUTER JOIN authors a', ['a.id' => 'book.author_id']);
 *
 * // The following work exactly as you'd expect.
 * $query->offset(10);
 * $query->limit(10);
 * $query->order(['name' => 'asc']);
 * $query->group(['max']);
 * ```
 *
 * ## Executing queries
 *
 * Once you've built up your query, you can execute it. There are all kinds
 * of methods available for SELECT-based queries.
 *
 * ```php
 * // Return a single author, or throw an exception.
 * $query->find();
 *
 * // Return a single author without throwing an exception.
 * $query->first();
 *
 * // Return all records, without any sort of limit.
 * $query->all();
 *
 * // Return the number of records that would be returned by all().
 * $query->count();
 *
 * // Return a number of authors, not exceeding the passed limit of 10.
 * $query->take(10);
 * ```
 *
 * There are also methods for modifying records.
 *
 * ```php
 * // Insert a new record, returning the number of affected rows.
 * $query->insert(['name' => 'H. Preen']);
 *
 * // Update a records, returning the number of affected rows.
 * $query->update(['name' => 'H. Preen']);
 *
 * // Delete records, returning number of affected rows.
 * $query->delete();
 * ```
 *
 * As well as facilities for executing raw SQL.
 *
 * ```php
 * $query->select('SELECT * FROM books WHERE email = ?', ['foo@example.com']);
 * $query->modify('DELETE FROM books WHERE email = ?', ['foo@example.com']);
 * ```
 *
 *
 * ## Complex expressions
 *
 * Each of `find`, `first`, `all`, `delete`, `where`, `whereAny`,
 * `having`, and `havingAny` take a few different types of expressions.
 * The types of expressions they take can range from very terse (such as
 * when selecting by primary key) to full-blown SQL.
 *
 * Take a look.
 *
 * ```php
 * // You can filter by ID, which is useful for find, first, and delete
 * $query->find(1);
 *
 * // You can filter by equality, which is useful for almost all methods
 * $query->all(['active' => true]);
 *
 * // It'll even figure out IN statements for ya
 * $query->all(['email' => [
 *   'rl.stine@example.com',
 *   'ka.applegate@example.com',
 *   'jk.rowling@example.com',
 * ]]);
 *
 * // You can filter using raw SQL, which is often easiest
 * // Hint: the second argument is used for escaped conditions
 * $query->first('email = ? and active',
 *   ['richie.brautwurst@example.com']);
 *
 * // You can even filter by more complex expressions...
 * use Mismatch\DB\Expression as e;
 *
 * // Like the every-useful LIKE filter
 * $query->where(['email' => e\like('%@example.com');
 *
 * // Or NOT, which comes up often
 * $query->whereAny(['email' => e\not('el.james@example.com')]);
 *
 * // Even IS NULL
 * $query->delete(['email' => e\null()]);
 * ```
 */
class Query implements IteratorAggregate
{
    use Query\From;
    use Query\Join;
    use Query\Where;
    use Query\Having;
    use Query\Order;
    use Query\Group;
    use Query\Limit;

    /**
     * @var  Connection  The connection to make requests against.
     */
    private $conn;

    /**
     * @var  string  The alias to use for unadorned columns
     */
    private $alias;

    /**
     * @var  string  The primary key to use for id shortcuts.
     */
    private $pk;

    /**
     * @var  array  Private parts, heh.
     */
    private $parts = [];

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
        return $this->toSelect()[0];
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

        list($query, $params) = $this->toSelect();

        return $this->select($query, $params);
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
     * Executes an insert, returning the last insert id.
     *
     * @param   array  $data
     * @return  int
     * @api
     */
    public function insert(array $data)
    {
        list($query, $params) = $this->toInsert($data);

        $this->select($query, $params);

        return $this->modify($query, $params);
    }

    /**
     * Executes an update, returning the number of rows affected.
     *
     * @param   array  $data
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  int
     * @api
     */
    public function update(array $data, $query = null, $conds = [])
    {
        if ($query) {
            $this->where($query, $conds);
        }

        list($query, $params) = $this->toUpdate($data);

        return $this->modify($query, $params);
    }

    /**
     * Executes a deletion.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  int
     * @api
     */
    public function delete($query = null, $conds = [])
    {
        if ($query) {
            $this->where($query, $conds);
        }

        list($query, $params) = $this->toDelete();

        return $this->modify($query, $params);
    }

    /**
     * Executes a raw select query.
     *
     * @param   string  $query
     * @param   array   $params
     * @return  Collection
     * @api
     */
    public function select($query, array $params = [])
    {
        $stmt = $this->conn->executeQuery($query, $params);

        // Wrap the statement in our own result type, so we have more
        // control over the interface that it exposes.
        return $this->prepareStatement($stmt);
    }

    /**
     * Executes a raw modification query.
     *
     * @param   string  $query
     * @param   array   $params
     * @return  Collection
     * @api
     */
    public function modify($query, array $params = [])
    {
        return $this->conn->executeUpdate($query, $params);
    }

    /**
     * Executes the passed callback inside of a transaction.
     *
     * @param   Closure  $fn
     * @return  self
     * @api
     */
    public function transactional(Closure $fn)
    {
        $this->conn->transactional($fn);

        return $this;
    }

    /**
     * Returns the last insert id.
     *
     * @return  int|string
     */
    public function lastInsertId()
    {
        $this->conn->lastInsertId();
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
    public function columns(array $columns)
    {
        return $this->addPart('select', $columns);
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
    private function toSelect()
    {
        if (!$this->hasPart('select')) {
            $this->setPart('select', ['*']);
        }

        $query[] = 'SELECT ' . $this->compileList('select');
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
     * Compiles the query as an UPDATE statement.
     *
     * @param   array  $data
     * @return  array
     */
    private function toUpdate($data)
    {
        $update = $this->compileUpdate($data);
        $query[] = 'UPDATE ' . $this->compileFrom(false);
        $query[] = 'SET ' . $update[0];
        $params = $update[1];

        if ($expr = $this->compileWhere()) {
            $query[] = $expr[0];
            $params = array_merge($params, $expr[1]);
        }

        $query = implode(array_filter($query), ' ');
        $query = $this->compileLimit($query);

        return [$query, $params];
    }

    /**
     * Compiles the query as an INSERT statement.
     *
     * @param   array  $data
     * @return  array
     */
    private function toInsert($data)
    {
        $insert = $this->compileInsert($data);
        $query[] = 'INSERT INTO ' . $this->compileFrom(false);
        $query[] = $insert[0];
        $params = $insert[1];

        $query = implode(array_filter($query), ' ');

        return [$query, $params];
    }

    /**
     * Compiles the query as a DELETE statement.
     *
     * @return  array
     */
    private function toDelete()
    {
        $query[] = 'DELETE FROM ' . $this->compileFrom(false);
        $params = [];

        if ($join = $this->compileJoin()) {
            $query[] = $join[0];
            $params = array_merge($params, $join[1]);
        }

        if ($expr = $this->compileWhere()) {
            $query[] = $expr[0];
            $params = array_merge($params, $expr[1]);
        }

        $query = implode(array_filter($query), ' ');
        $query = $this->compileLimit($query);

        return [$query, $params];
    }

    /**
     * Sets a part with a brand new value.
     *
     * @param   string  $name
     * @param   mixed   $value
     * @return  $this
     */
    private function setPart($name, $value)
    {
        $this->parts[$name] = $value;

        return $this;
    }

    /**
     * Adds to a part as if it were an array.
     *
     * @param   string  $name
     * @param   array   $value
     * @return  $this
     */
    private function addPart($name, array $value)
    {
        $this->parts[$name] = array_merge($this->getPart($name, []), $value);

        return $this;
    }

    /**
     * Returns a part, using the default if it doesn't exist.
     *
     * @param   string  $name
     * @param   mixed   $default
     * @return  mixed
     */
    private function getPart($name, $default = null)
    {
        if (!$this->hasPart($name)) {
            $this->setPart($name, $default);
        }

        return $this->parts[$name];
    }

    /**
     * Returns whether or not the query has a particular part.
     *
     * @param   string  $name
     * @return  bool
     */
    private function hasPart($name)
    {
        return isset($this->parts[$name]);
    }

    /**
     * Prepares a list-based clause of a SQL query.
     *
     * @param  array  $query
     * @param  bool   $alias
     */
    private function compileList($type, $aliasFrom = true)
    {
        if (!$this->hasPart($type)) {
            return;
        }

        $parts = [];

        foreach ($this->getPart($type, []) as $source => $alias) {
            // Allow no aliasing as well, as denoted by an it key
            if (is_int($source)) {
                $source = $alias;
                $alias = null;
            }

            switch ($type) {
                // Turn SELECTs into table.column AS alias
                case 'select':
                    $source = Expr\columnize($source, $this->alias);
                    $parts[] = Expr\alias($source, $alias);
                    break;
            }
        }

        return implode($parts, ', ');
    }

    /**
     * @param   array  $data
     * @return  array
     */
    private function compileUpdate($data)
    {
        $parts = [];
        $binds = [];

        foreach ($data as $column => $value) {
            $parts[] = sprintf('%s = ?', $column);
            $binds[] = $value;
        }

        return [implode($parts, ', '), $binds];
    }

    /**
     * @param   array  $data
     * @return  array
     */
    private function compileInsert($data)
    {
        $columns = [];
        $values = [];
        $binds = [];

        foreach ($data as $column => $value) {
            $columns[] = $column;
            $values[] = '?';
            $binds[] = $value;
        }

        $columns = implode($columns, ',');
        $values = implode($values, ',');
        $query = sprintf('(%s) VALUES (%s)', $columns, $values);

        return [$query, $binds];
    }
}
