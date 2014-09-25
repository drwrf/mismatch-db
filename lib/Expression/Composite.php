<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\DB\Expression;

use Mismatch\DB\Expression as e;

class Composite implements ExpressionInterface
{
    /**
     * @var  string
     */
    private $alias;

    /**
     * @var  array
     */
    private $expr = [];

    /**
     * @var  bool
     */
    private $compiled = false;

    /**
     * @return  string
     */
    public function __toString()
    {
        return $this->getExpr();
    }

    /**
     * Combines all expressions passed using an AND.
     *
     * @param  string|array  $expr
     * @param  array         $binds
     * @return $this
     */
    public function all($expr, array $binds = [])
    {
        $this->expr = array_merge($this->expr, $this->addConditions('AND', $expr, $binds));

        return $this;
    }

    /**
     * Combines all expressions passed using an AND.
     *
     * @param  string|array  $expr
     * @param  array         $binds
     * @return $this
     */
    public function any($expr, array $binds = [])
    {
        $this->expr = array_merge($this->expr, $this->addConditions('OR', $expr, $binds));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpr($column = null)
    {
        return $this->compile()[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getBinds()
    {
        return $this->compile()[1];
    }

    /**
     * Allows setting a table alias to use for all columns passed
     * to hash-based expressions.
     *
     * @param  string  $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        $this->compiled = false;

        return $this;
    }

    /**
     * Compiles the expression into a string and a set of params.
     *
     * @return [$expr, $binds]
     */
    public function compile()
    {
        if (!$this->compiled) {
            $expr = '';
            $binds = [];

            foreach ($this->expr as $part) {
                if ($expr) {
                    $expr .= ' ' . $part['type'] . ' ';
                }

                $expr .= $part['expr'];
                $binds = array_merge($binds, $part['binds']);
            }

            $this->compiled = [$expr, $binds];
        }

        return $this->compiled;
    }

    /**
     * @return  array
     */
    private function addConditions($type, $expr, array $binds)
    {
        $ret = [];

        // Ensure we mark the need for recompilation.
        $this->compiled = false;

        // Passing a simple string like 'foo.bar = ?', [$bar] should
        // work fine, so we can skip the complicated array syntax.
        if (is_string($expr)) {
            return [[
                'expr' => $expr,
                'binds' => $binds,
                'type' => $type,
            ]];
        }

        foreach ($expr as $column => $value) {
            // Allow passing a literal string, which is useful for
            // completely literal expressions (such as those used for joins).
            if (is_int($column)) {
                $ret[] = [
                    'expr' => $value,
                    'type' => $type,
                    'binds' => [],
                ];

                continue;
            }

            // Try and provide a table alias if possible.
            $column = e\columnize($column, $this->alias);

            // And automatically detect an IN if possible.
            if (is_array($value)) {
                $value = e\in($value);
            }

            if (!($value instanceof Expression)) {
                $value = e\eq($value);
            }

            $ret[] = [
                'expr' => $value->getExpr($column),
                'binds' => $value->getBinds(),
                'type' => $type,
            ];
        }

        return $ret;
    }
}
