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
     * @param  array         $params
     * @return $this
     */
    public function all($expr, array $params = [])
    {
        $this->expr[] = ['AND', $expr, $params];

        return $this;
    }

    /**
     * Combines all expressions passed using an AND.
     *
     * @param  string|array  $expr
     * @param  array         $params
     * @return $this
     */
    public function any($expr, array $params = [])
    {
        $this->expr[] = ['OR', $expr, $params];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpr($column = null)
    {
        return $this->compile()['expr'];
    }

    /**
     * {@inheritDoc}
     */
    public function getBinds()
    {
        return $this->compile()['params'];
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
     * @return array
     */
    private function compile()
    {
        if ($this->compiled) {
            return $this->compiled;
        }

        $expr = '';
        $params = [];

        foreach ($this->expr as $parts) {
            // We store the arguments passed to any and all in an array
            // and only unpack them when compiling, so now's the time.
            $parts = $this->compilePart($parts[0], $parts[1], $parts[2]);

            foreach ($parts as $part) {
                // Only start adding the operands if we've started adding
                // to the expression. Syntax errors would occur otherwise.
                if ($expr) {
                    $expr .= ' ' . $part['type'] . ' ';
                }

                $expr .= $part['expr'];
                $params = array_merge($params, $part['params']);
            }
        }

        $this->compiled = [
            'expr' => $expr,
            'params' => $params,
        ];

        return $this->compiled;
    }

    /**
     * @return  array
     */
    private function compilePart($type, $expr, array $params)
    {
        $ret = [];

        // Passing a simple string like 'foo.bar = ?', [$bar] should
        // work fine, so we can skip the complicated array syntax.
        if (is_string($expr)) {
            return [[
                'expr' => $expr,
                'params' => $params,
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
                    'params' => [],
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
                'params' => $value->getBinds(),
                'type' => $type,
            ];
        }

        return $ret;
    }
}
