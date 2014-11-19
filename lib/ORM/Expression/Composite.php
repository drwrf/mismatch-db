<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Expression;

use Mismatch\ORM\Expression as e;

class Composite implements ExpressionInterface
{
    /**
     * @var  array
     */
    private $expr = [];

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
    public function all($expr, $params = [])
    {
        $this->expr[] = ['AND', $expr, (array) $params];

        return $this;
    }

    /**
     * Combines all expressions passed using an AND.
     *
     * @param  string|array  $expr
     * @param  array         $params
     * @return $this
     */
    public function any($expr, $params = [])
    {
        $this->expr[] = ['OR', $expr, (array) $params];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpr($alias = null)
    {
        return $this->compile($alias)['expr'];
    }

    /**
     * {@inheritDoc}
     */
    public function getBinds()
    {
        return $this->compile()['params'];
    }

    /**
     * Compiles the expression into a string and a set of params.
     *
     * @return array
     */
    private function compile($alias = null)
    {
        $expr = '';
        $params = [];

        foreach ($this->expr as $parts) {
            // We store the arguments passed to any and all in an array
            // and only unpack them when compiling, so now's the time.
            $parts = $this->compilePart(
                $alias, $parts[0], $parts[1], $parts[2]);

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

        return [
            'expr' => $expr,
            'params' => $params,
        ];
    }

    /**
     * @return  array
     */
    private function compilePart($alias, $type, $expr, array $params)
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

        // Allow passing completely bare expressions.
        if ($expr instanceof ExpressionInterface) {
            return [[
                'expr' => $expr->getExpr($alias),
                'params' => $expr->getBinds(),
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
            $column = e\columnize($column, $alias);

            // And automatically detect an IN if possible.
            if (is_array($value)) {
                $value = e\in($value);
            }

            if (!($value instanceof ExpressionInterface)) {
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
