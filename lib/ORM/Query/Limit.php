<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Query;

use Mismatch\ORM\Expression\Composite;

/**
 * Adds LIMIT functionality to a query builder.
 */
trait Limit
{
    /**
     * @var  int  The maximum number of results to return
     */
    private $limit;

    /**
     * @var  Composite  The offset to start limiting results
     */
    private $offset;

    /**
     * Determines how many results to return.
     *
     * Passing one will give you a single model back.
     *
     * @param  int  $limit
     * @return self
     * @api
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Determines the offset of results.
     *
     * @param  int  $offset
     * @return self
     * @api
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Adds the LIMIT and OFFSET parts to a query.
     *
     * @param  string  $query
     */
    private function compileLimit($query)
    {
        $limit = $this->limit;
        $offset = $this->offset;

        if ($limit || $offset) {
            return $this->conn
                ->getDatabasePlatform()
                ->modifyLimitQuery($query, $limit, $offset);
        }

        return $query;
    }
}
