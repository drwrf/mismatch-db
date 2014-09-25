<?php

namespace Mismatch\DB;

use Countable;
use Iterator;
use PDO;
use UnexpectedValueException;

class Collection implements Iterator, Countable
{
    /**
     * @var  Doctrine\DBAL\Driver\Statement
     */
    private $stmt;

    /**
     * @var  string
     */
    private $mode = 'array';

    /**
     * @var  int
     */
    private $position = 0;

    /**
     * @var  array
     */
    private $results = [];

    /**
     * Constructor.
     *
     * @param  Doctrine\DBAL\Driver\Statement  $stmt
     */
    public function __construct($stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * Allows choosing a mode to fetch the data as.
     *
     * @param   string  $mode
     * @return  $this
     */
    public function fetchAs($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Implementation of Iterator.
     *
     * @see  http://php.net/manual/en/class.iterator.php
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Implementation of Iterator.
     *
     * @see  http://php.net/manual/en/class.iterator.php
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
     * @see  http://php.net/manual/en/class.iterator.php
     */
    public function current()
    {
        $result = $this->results[$this->position];

        return $this->mapResult($result);
    }

    /**
     * Implementation of Iterator.
     *
     * @see  http://php.net/manual/en/class.iterator.php
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Implementation of Iterator.
     *
     * @see  http://php.net/manual/en/class.iterator.php
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Implementation of the Countable interface.
     *
     * @see http://php.net/manual/en/class.countable.php
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
     */
    protected function mapResult(array $result)
    {
        if ($this->mode === 'array') {
            return $result;
        }

        if ($this->mode === 'object') {
            return (object) $result;
        }

        if (class_exists($this->mode)) {
            return new $this->mode($result);
        }

        throw new UnexpectedValueException(sprintf('Invalid mode "%s".', $mode));
    }
}
