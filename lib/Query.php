<?php

namespace Mismatch\SQL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Mismatch\SQL\Expression as Expr;
use Mismatch\SQL\Result;
use IteratorAggregate;
use Countable;
use Closure;
use DateTime;
use PDO;
use DomainException;

class Query implements IteratorAggregate, Countable
{
    /**
     * @var  Doctrine\DBAL\Connection  The connection to make requests against.
     */
    private $conn;

    /**
     * @var  string  The alias to use for unadorned columns
     */
    private $alias;

    /**
     * @var  array  Private parts, heh.
     */
    private $parts = [];

    /**
     * @var  mixed  A cached result, if a query has already been made.
     */
    private $result;

    /**
     * Constructor.
     *
     * @param   Doctrine\DBAL\Connection     $conn
     * @param   string                       $pk
     */
    public function __construct($conn, $pk = 'id')
    {
        $this->conn = $conn;
        $this->pk = $pk;
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
        $this->result = null;
    }

    /**
     * Finds a record by its id and returns the first
     * value or throws an exception if none exists.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @throws  DomainException
     * @return  mixed
     */
    public function find($query, $conds = [])
    {
        $result = $this->first($query, $conds);

        if (!$result) {
            throw new DomainException(sprintf(
                'Could not find a single record using "%s".', $this));
        }

        return $result;
    }

    /**
     * Finds a record by its id and returns the first
     * value or returns null if none exists.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  mixed
     */
    public function first($query = null, $conds = [])
    {
        if ($query && is_int($query)) {
            $query = [$this->pk => $query];
        }

        if (!$this->hasPart('limit')) {
            $this->limit(1);
        }

        $result = $this->all($query, $conds);

        if ($result->valid()) {
            return $result->current();
        }
    }

    /**
     * Finds all records.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  Doctrine\DBAL\Driver\Statement
     */
    public function all($query = null, $conds = [])
    {
        if ($query) {
            $this->where($query, $conds);
        }

        if (!$this->result) {
            list($query, $params) = $this->toSelect();
            $this->result = $this->raw($query, $params);
        }

        return $this->result;
    }

    /**
     * Returns the total number of records in the query.
     *
     * @return  int
     */
    public function count($mode = COUNT_NORMAL)
    {
        return $this->all()->count();
    }

    /**
     * Executes an update, returning the number of rows affected.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  int
     */
    public function update($query = null, $conds = [])
    {
        if ($query && is_int($query)) {
            $query = [$this->pk => $query];
        }

        if ($query) {
            $this->where($query, $conds);
        }

        list($query, $params) = $this->toUpdate();

        return count($this->raw($query, $params));
    }

    /**
     * Executes an insert, returning the last insert id.
     *
     * @return  int
     */
    public function insert()
    {
        list($query, $params) = $this->toInsert();

        $this->raw($query, $params);

        return count($this->raw($query, $params));
    }

    /**
     * Executes a deletion.
     *
     * @param   mixed  $query
     * @param   mixed  $conds
     * @return  int
     */
    public function delete($query = null, $conds = [])
    {
        if ($query && is_int($query)) {
            $query = [$this->pk => $query];
        }

        if ($query) {
            $this->where($query, $conds);
        }

        list($query, $params) = $this->toDelete();

        return count($this->raw($query, $params));
    }

    /**
     * Executes a raw query.
     *
     * @param   string  $query
     * @param   array   $params
     * @return  Doctrine\DBAL\Driver\Statement
     */
    public function raw($query, array $params = [])
    {
        $types = $this->prepareTypes($params);
        $stmt = $this->conn->executeQuery($query, $params, $types);

        // Wrap the statement in our own type, so we have more
        // control over the interface that it exposes.
        return $this->prepareStatement($stmt);
    }

    /**
     * Executes the passed callback inside of a transaction.
     *
     * @param   Closure  $fn
     */
    public function transactional(Closure $fn)
    {
        $this->conn->transactional($fn);
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
     * <code>
     *     // Aliases are supported as array keys
     *     $query->select(['column', 'column' => 'alias']);
     * </code>
     *
     * @param  array  $columns
     */
    public function select(array $columns)
    {
        return $this->addPart('select', $columns);
    }

    /**
     * Sets the table or tables to select data from.
     *
     * By default, the first table that is passed here will be used
     * as the "main" table, which means that we'll assume unadorned
     * columns will be selected from this table.
     *
     * @param   mixed  $table
     * @return  $this
     */
    public function from($table)
    {
        if (!$this->hasPart('from')) {
            $this->alias = is_array($table) ? current($table) : $table;
        }

        $this->addPart('from', (array) $table);

        return $this;
    }

    /**
     * Adds a single JOIN statement to the query.
     *
     * If $join is an attribute that exists on the model, then
     * that attribute will be allowed to create the join.
     *
     * <code>
     * // INNER JOIN is added by default.
     * Book::all()->join('authors a', ['a.id' => 'book.author_id']);
     *
     * // Although different types of joins can be specified.
     * Book::all()->join('LEFT OUTER JOIN authors a', ['a.id' => 'book.author_id']);
     * </code>
     *
     * @param  string  $table
     * @param  mixed   $conds
     */
    public function join($table, $conds = [])
    {
        return $this->addPart('join', [
            $table => $conds,
        ]);
    }

    /**
     * Adds a set of AND filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     */
    public function where($conds, array $binds = [])
    {
        $this->getComposite('where')->all($conds, $binds);

        return $this;
    }

    /**
     * Adds a set of OR filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     */
    public function whereAny($conds, array $binds = [])
    {
        $this->getComposite('where')->any($conds, $binds);

        return $this;
    }

    /**
     * Adds a set of AND HAVING filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     */
    public function having($conds, array $binds = [])
    {
        $this->getComposite('having')->all($conds, $binds);

        return $this;
    }

    /**
     * Adds a set of OR HAVING filters to a query chain.
     *
     * @param  mixed  $conds
     * @param  array  $binds
     */
    public function havingAny($conds, array $binds = [])
    {
        $this->getComposite('having')->any($conds, $binds);

        return $this;
    }

    /**
     * Determines the offset of results.
     *
     * @param  int  $limit
     */
    public function offset($offset)
    {
        return $this->setPart('offset', $offset);
    }

    /**
     * Determines how many results to return.
     *
     * Passing one will give you a single model back.
     *
     * @param  int  $limit
     */
    public function limit($limit)
    {
        return $this->setPart('limit', $limit);
    }


    /**
     * Determines the columns to group by.
     *
     * @param  array  $columns
     */
    public function group(array $columns)
    {
        return $this->addPart('group', $columns);
    }

    /**
     * Determines the columns to order by.
     *
     * @param  array   $columns
     */
    public function order(array $columns)
    {
        return $this->addPart('order', $columns);
    }

    /**
     * Adds values to the list that should be changed.
     *
     * @param  array  $values
     */
    public function set(array $values)
    {
        return $this->addPart('set', $values);
    }

    /**
     * Implementation of IteratorAggregate
     *
     * @return  Iterator
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
     */
    protected function prepareStatement($stmt)
    {
        return new Result($stmt);
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
        $query[] = 'FROM ' . $this->compileList('from');
        $params = [];

        if ($join = $this->compileJoin()) {
            $query[] = $join[0];
            $params = array_merge($params, $join[1]);
        }

        if ($expr = $this->compileExpression('where')) {
            $query[] = sprintf('WHERE %s', $expr[0]);
            $params = array_merge($params, $expr[1]);
        }

        if ($group = $this->compileList('group')) {
            $query[] = 'GROUP BY ' . $group;
        }

        if ($expr = $this->compileExpression('having')) {
            $query[] = sprintf('HAVING %s', $expr[0]);
            $params = array_merge($params, $expr[1]);
        }

        if ($order = $this->compileList('order')) {
            $query[] = 'ORDER BY ' . $order;
        }

        $query = implode(array_filter($query), ' ');
        $query = $this->compileLimit($query);

        return [$query, $params];
    }

    /**
     * Compiles the query as an UPDATE statement.
     *
     * @return  array
     */
    private function toUpdate()
    {
        $update = $this->compileUpdate();
        $query[] = 'UPDATE ' . $this->compileList('from', false);
        $query[] = 'SET ' . $update[0];
        $params = $update[1];

        if ($expr = $this->compileExpression('where')) {
            $query[] = sprintf('WHERE %s', $expr[0]);
            $params = array_merge($params, $expr[1]);
        }

        $query = implode(array_filter($query), ' ');
        $query = $this->compileLimit($query);

        return [$query, $params];
    }

    /**
     * Compiles the query as an INSERT statement.
     *
     * @return  array
     */
    private function toInsert()
    {
        $insert = $this->compileInsert();
        $query[] = 'INSERT INTO ' . $this->compileList('from', false);
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
        $query[] = 'DELETE FROM ' . $this->compileList('from', false);
        $params = [];

        if ($join = $this->compileJoin()) {
            $query[] = $join[0];
            $params = array_merge($params, $join[1]);
        }

        if ($expr = $this->compileExpression('where')) {
            $query[] = sprintf('WHERE %s', $expr[0]);
            $params = array_merge($params, $expr[1]);
        }

        $query = implode(array_filter($query), ' ');
        $query = $this->compileLimit($query);

        return [$query, $params];
    }

    /**
     * Compiles the JOIN clause of a SQL query.
     *
     * @return  array
     */
    private function compileJoin()
    {
        if (!$this->hasPart('join')) {
            return null;
        }

        $parts = [];
        $params = [];

        foreach ($this->getPart('join', []) as $table => $conds) {
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
     * @param  array  $on
     */
    private function compileOn($on)
    {
        if (!$on) {
            return;
        }

        if ($on instanceof Expr\Composite) {
            return $on->compile();
        }

        $expr = new Expr\Composite();

        foreach ($on as $owner => $related) {
            $expr->all([ sprintf('%s = %s', $owner, $related) ]);
        }

        return $expr->compile();
    }

    /**
     * @param   string  $type
     * @return  Mismatch\Composite
     */
    private function getComposite($type)
    {
        if (!$this->hasPart($type)) {
            $this->setPart($type, (new Expr\Composite())->setAlias($this->alias));
        }

        return $this->getPart($type);
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
        $this->result = null;

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
        $this->result = null;

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

        $this->result = null;

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
                    $parts[] = $this->alias($source, $alias);
                    break;

                // Turn FROMs into table AS alias
                case 'from':
                    $parts[] = $aliasFrom ? $this->alias($source, $alias) : $source;
                    break;

                // Turn ORDER BYs into table.column ASC/DESC
                case 'order':
                    $parts[] = Expr\columnize($source, $this->alias) . ' ' . strtoupper($alias);
                    break;

                // Turn GROUP BYs into table.column
                case 'group':
                    $parts[] = Expr\columnize($source, $this->alias);
                    break;
            }
        }

        return implode($parts, ', ');
    }

    /**
     * @return  array
     */
    private function compileUpdate()
    {
        if (!$this->hasPart('set')) {
            throw new DomainException();
        }

        $parts = [];
        $binds = [];

        foreach ($this->getPart('set') as $column => $value) {
            $parts[] = sprintf('%s = ?', $column);
            $binds[] = $value;
        }

        return [implode($parts, ', '), $binds];
    }

    /**
     * @return  array
     */
    private function compileInsert()
    {
        if (!$this->hasPart('set')) {
            throw new DomainException(
                'Cannot perform an insert if there are no values to insert. ' .
                'Did you forget to call "set()" before calling "insert()"?');
        }

        $columns = [];
        $values = [];
        $binds = [];

        foreach ($this->getPart('set') as $column => $value) {
            $columns[] = $column;
            $values[] = '?';
            $binds[] = $value;
        }

        $columns = implode($columns, ',');
        $values = implode($values, ',');
        $query = sprintf('(%s) VALUES (%s)', $columns, $values);

        return [$query, $binds];
    }

    /**
     * Prepares an expression clause of a SQL query, including
     * WHERE and HAVING clauses.
     *
     * @param  string  $type
     * @param  array   $query
     * @param  array   $params
     */
    private function compileExpression($type)
    {
        if (!$this->hasPart($type)) {
            return;
        }

        $expr = $this->getPart($type);

        if ($expr) {
            return $expr->compile();
        }
    }

    /**
     * Adds the LIMIT and OFFSET parts to a query.
     *
     * @param  string  $query
     */
    private function compileLimit($query)
    {
        $limit = $this->getPart('limit');
        $offset = $this->getPart('offset');

        if ($limit || $offset) {
            return $this->conn
                ->getDatabasePlatform()
                ->modifyLimitQuery($query, $limit, $offset);
        }

        return $query;
    }

    /**
     * Creates a list of types from a list of parameters, so
     * that PDO can properly translate the value for the RDBMS.
     *
     * @return  array
     */
    private function prepareTypes($params)
    {
        $types = [];

        foreach ($params as $key => $value) {
            $types[$key] = $this->detectType($value);
        }

        return $types;
    }

    /**
     * Attempts to detect the doctrine type of a particular value.
     *
     * @param  mixed  $value
     */
    private function detectType($value)
    {
        if (is_integer($value)) {
            return Type::INTEGER;
        }

        if (is_bool($value)) {
            return Type::BOOLEAN;
        }

        if (is_array($value)) {
            return is_integer(current($value))
                ? Connection::PARAM_INT_ARRAY
                : Connection::PARAM_STR_ARRAY;
        }

        if ($value instanceof DateTime) {
            return Type::DATETIME;
        }

        return PDO::PARAM_STR;
    }

    /**
     * Creates an alias for a column or table if the alias is provided.
     *
     * @param   string  $source
     * @param   string  $alias
     * @return  string
     */
    private function alias($source, $alias)
    {
        if (is_string($alias) && $alias) {
            return sprintf("%s AS %s", $source, $alias);
        } else {
            return sprintf("%s", $source);
        }
    }
}
