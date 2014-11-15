<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM;

use Doctrine\DBAL\Driver\Statement;
use Countable;
use Iterator;
use PDO;
use UnexpectedValueException;

/**
 * Represents a collection of results from a query.
 */
class Collection implements Iterator, Countable
{
    /**
     * The statement that this collection wraps over.
     *
     * @var  Statement
     */
    private $stmt;

    /**
     * The strategy to use when returning individual results.
     *
     * @var  string
     */
    private $strategy = 'array';

    /**
     * The current position we're at in the list of results.
     *
     * @var  int
     */
    private $position = 0;

    /**
     * A cached list of results, used in case we iterate over this
     * collection more than one time.
     *
     * @var  array
     */
    private $results = [];

    /**
     * Constructor.
     *
     * @param  Statement  $stmt
     */
    public function __construct(Statement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * Affords choosing a strategy to fetch the data as.
     *
     * Available strategies include:
     *
     *  - `array` - Return each result as an associative array
     *  - `object` - Return each result as an instance of StdClass
     *  - A valid callable, in which case the result will be passed
     *    as the first argument to the callable, for each result.
     *  - A valid class name, in which case the result will be passed
     *    to the class' constructor, and the instance will be returned.
     *
     * @param   string|callable  $strategy
     * @return  $this
     */
    public function fetchAs($strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Implementation of Iterator.
     *
     * @link  http://php.net/manual/en/class.iterator.php
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Implementation of Iterator.
     *
     * @link  http://php.net/manual/en/class.iterator.php
     */
    public function valid()
    {
        if (isset($this->results[$this->position])) {
            return true;
        }

        $result = $this->stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $this->results[$this->position] = $result;
            return true;
        }

        return false;
    }

    /**
     * Implementation of Iterator.
     *
     * @link  http://php.net/manual/en/class.iterator.php
     */
    public function current()
    {
        $result = $this->results[$this->position];

        return $this->mapResult($result);
    }

    /**
     * Implementation of Iterator.
     *
     * @link  http://php.net/manual/en/class.iterator.php
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Implementation of Iterator.
     *
     * @link  http://php.net/manual/en/class.iterator.php
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Implementation of the Countable interface.
     *
     * @param  mixed  $mode
     * @return int
     * @link   http://php.net/manual/en/class.countable.php
     */
    public function count($mode = COUNT_NORMAL)
    {
        return $this->stmt->rowCount();
    }

    /**
     * Hook for mapping bare array results into models.
     *
     * @param   array  $result
     * @return  mixed
     * @api
     */
    protected function mapResult(array $result)
    {
        if ($this->strategy === 'array') {
            return $result;
        }

        if ($this->strategy === 'object') {
            return (object) $result;
        }

        if (is_callable($this->strategy)) {
            return call_user_func($this->strategy, $result);
        }

        if (class_exists($this->strategy)) {
            return new $this->strategy($result);
        }

        throw new UnexpectedValueException(
            sprintf('Invalid strategy "%s".', $this->strategy));
    }
}
