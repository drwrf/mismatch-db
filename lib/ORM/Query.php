<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM;

use Mismatch\ORM\Expression as e;
use Mismatch\ORM\Collection;
use Mismatch\ORM\Exception\ModelNotFoundException;
use IteratorAggregate;
use Countable;
use Closure;
use Exception;

/**
 * Handles building an executing SQL queries.
 *
 * In conjunction with the factories provided by `Mismatch\ORM\Expression`,
 * this becomes a powerful tool for building the queries common to CRUD
 * operations in web applications.
 *
 * As a quick example, let's find ten active authors who recently signed
 * up for our service, and let's order them by name.
 *
 * ```php
 * use Mismatch\ORM\Query;
 * use Mismatch\ORM\Expression as e;
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
 * ## Retrieving data
 *
 * For most applications, the majority of your queries will be used to
 * retrieve data from your database. As such, `Mismatch\ORM` has quite
 * a few methods available to retrieve data.
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
 * ```
 *
 * ## Conditions
 *
 * `Mismatch\ORM` provides a few methods to add `WHERE` clauses to your
 * queries, so as to limit the number of records returned. These methods
 * can take a plethora of arguments, based on your use-case.
 *
 * ```php
 * // Adds "WHERE id = 1" to your query.
 * $query->where(1);
 *
 * // Adds "WHERE id IN (1, 2)" to your query.
 * $query->where([1, 2]);
 *
 * // Adds "WHERE email = 'rl.stine@example.com'" to your query.
 * $query->where(['email' => 'rl.stine@example.com']);
 *
 * // Adds "WHERE id IN (1, 2)" to your query.
 * $query->where(['id' => [1, 2]]);
 *
 * // Adds "WHERE email LIKE '%example.com'".
 * $query->where('email LIKE ?', ['%example.com']);
 * ```
 *
 * More complex expressions are also available using the API provided
 * by `Mismatch\ORM\Expression`. The following expressions are available:
 *
 * ```php
 * use Mismatch\ORM\Expression as e;
 *
 * // > and >=
 * $query->where(['logins' => e\gt(10)]);
 * $query->where(['logins' => e\gte(10)]);
 *
 * // < and <=
 * $query->where(['logins' => e\lt(10)]);
 * $query->where(['logins' => e\lte(10)]);
 *
 * // Date comparisons make more semantic sense with "before" and "after".
 * $query->where(['signup_date' => e\before('2014-01-01')]);
 * $query->where(['signup_date' => e\after('2014-01-01')]);
 *
 * // BETWEEN
 * $query->where(['signup_date' => e\between('2014-01-01', '2015-01-01')]);
 *
 * // LIKE
 * $query->where(['email' => e\like('%example.com')]);
 *
 * // IS NULL and blank (falsey or null)
 * $query->where(['email' => e\null()]);
 * $query->where(['email' => e\blank()]);
 *
 * // Less than and less than or equal to.
 * $query->where(['logins' => e\lt(10)]);
 * $query->where(['logins' => e\lte(10)]);
 *
 * // Negate using "not".
 * $query->where(['logins' => e\not(e\gt(10))]);
 * ```
 *
 * There are also a few methods other than `where` that can aid in
 * building complex conditions. All of these methods are chainable,
 * so you can easily build up compound conditions.
 *
 * ```php
 * // Joins conditions with AND
 * $query->where();
 *
 * // Joins conditions with OR
 * $query->whereAny();
 *
 * // Joins conditions with AND NOT
 * $query->exclude();
 *
 * // Joins conditions with OR NOT
 * $query->excludeAny();
 * ```
 *
 * As a final note, all of the finder methods—`find`, `first`, `all`, and
 * `count`—take the same arguments as `where`, meaning you can easily add
 * conditions to them without having to first call `where`. This means
 * that the following two lines of code are functionally equivalent.
 *
 * ```php
 * $query->where(['email' => 'hank.b@example.com'])->all();
 * $query->all(['email' => 'hank.b@example.com']);
 * ```
 *
 * ## Other conditions
 *
 * Aside from conditions you can also add joins, limits, offsets,
 * aggregates, and sorting capabilities to your queries.
 *
 * ```php
 * // Select specific columns. Aliases are supported as array keys
 * $query->select(['column', 'column' => 'alias']);
 *
 * // GROUP BY
 * $query->group(['max']);
 *
 * // HAVING—these methods have the same signature and behavior as "where"
 * $query->having(['sum' => e\gt(5)]);
 * $query->havingAny(['max' => e\lt(10)]);
 *
 * // INNER JOIN is added by default.
 * $query->join('authors a', ['a.id' => 'book.author_id']);
 *
 * // Although different types of joins can be specified.
 * $query->join('LEFT OUTER JOIN authors a', ['a.id' => 'book.author_id']);
 *
 * // The following work exactly as you'd expect.
 * $query->order(['name' => 'asc']);
 * $query->offset(10);
 * $query->limit(10);
 * ```
 *
 * ## Modifying data
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
 * // This also takes the same arguments as `where`.
 * $query->delete();
 * ```
 *
 * As well as facilities for executing raw SQL.
 *
 * ```php
 * $query->raw('SELECT * FROM books WHERE email = ?', ['foo@example.com']);
 * ```
 */
class Query implements IteratorAggregate, Countable
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
     * @var  string  The primary key to use for id shortcuts.
     */
    private $pk;

    /**
     * The strategy to use when returning individual results.
     *
     * @var  string
     */
    private $strategy;

    /**
     * @var  array  The columns to select.
     */
    private $select = [];

    /**
     * Constructor.
     *
     * @param   Connection    $conn
     * @param   string|array  $table
     * @param   string        $pk
     */
    public function __construct($conn, $table = null, $pk = 'id')
    {
        $this->conn = $conn;
        $this->pk = $pk;

        if ($table) {
            $this->from($table);
        }
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
        $this->select = array_merge($this->select, $columns);

        return $this;
    }

    /**
     * Attempts to find a single record.
     *
     * If no record is returned, then an exception is thrown.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @throws  ModelNotFoundException
     * @return  mixed
     * @api
     */
    public function find($query = null, $conds = null)
    {
        $result = $this->first($query, $conds);

        if (!$result) {
            throw new ModelNotFoundException($this->table, $query);
        }

        return $result;
    }

    /**
     * Attempts to find a single record.
     *
     * If no record is found, then null is returned.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  mixed
     * @api
     */
    public function first($query = null, $conds = null)
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
    public function all($query = null, $conds = null)
    {
        if ($query) {
            $this->where($query, $conds);
        }

        list($query, $params) = $this->toSelect();

        return $this->raw($query, $params);
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
     * Returns the total number of records in the query.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  int
     * @api
     */
    public function count($query = null, $conds = null)
    {
        return $this->all($query, $conds)->count();
    }

    /**
     * Executes an insert, returning the last insert id.
     *
     * @param   array  $data
     * @return  int
     * @api
     */
    public function insert($data)
    {
        list($query, $params) = $this->toInsert($data);

        return $this->raw($query, $params);
    }

    /**
     * Executes an update, returning the number of rows affected.
     *
     * @param   array  $data
     * @return  int
     * @api
     */
    public function update($data)
    {
        list($query, $params) = $this->toUpdate($data);

        return $this->raw($query, $params);
    }

    /**
     * Executes a deletion.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  int
     * @api
     */
    public function delete($query = null, $conds = null)
    {
        if ($query) {
            $this->where($query, $conds);
        }

        list($query, $params) = $this->toDelete();

        return $this->raw($query, $params);
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

        // No columns? That was a INSERT, UPDATE, or DELETE, so
        // just return the number of affected rows.
        if ($stmt->columnCount() === 0) {
            return $stmt->rowCount();
        }

        // Wrap the statement in our own result type, so we have more
        // control over the interface that it exposes.
        return $this->prepareStatement($stmt, $this->strategy);
    }

    /**
     * Executes a function inside of a transaction
     *
     * @param   Closure  $fn
     * @return  mixed
     * @throws  Exception
     */
    public function transactional(Closure $fn)
    {
        $this->conn->beginTransaction();

        try {
            $result = $fn($this);
            $this->conn->commit();

            return $result;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Sets the strategy to use for returning results from a SELECT.
     *
     * @see Collection::fetchAs() The possible values of $strategy.
     *
     * @param  string  $strategy
     * @return self
     */
    public function fetchAs($strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Hook to allow preparation of a SQL result just before
     * it's returned to the caller.
     *
     * @param  Doctrine\DBAL\Driver\Statement  $stmt
     * @param  string                          $strategy
     * @api
     */
    protected function prepareStatement($stmt, $strategy)
    {
        $collection = new Collection($stmt);

        if ($strategy) {
            $collection->fetchAs($strategy);
        }

        return $collection;
    }

    /**
     * Compiles the query as a SELECT statement.
     *
     * @return  array
     */
    private function toSelect()
    {
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

        if ($order = $this->compileOrder()) {
            $query[] = $order;
        }

        $query = implode(array_filter($query), ' ');
        $query = $this->compileLimit($query);

        return [$query, $params];
    }

    /**
     * Compiles the columns to select in a SELECT.
     *
     * @return  string
     */
    private function compileColumns()
    {
        if (!$this->select) {
            $this->select = ['*'];
        }

        $parts = [];

        foreach ($this->select as $column => $alias) {
            // Allow no aliasing as well, as denoted by an it key
            if (is_int($column)) {
                $parts[] = e\columnize($alias, $this->alias);
            } else {
                $column = e\columnize($column, $this->alias);
                $parts[] = e\alias($column, $alias);
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
