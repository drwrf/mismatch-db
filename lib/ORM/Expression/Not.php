<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Expression;

use Mismatch\ORM\Expression as e;

/**
 * Handles negating expressions.
 */
class Not extends Expression
{
    /**
     * @var  mixed  The child expression we're negating
     */
    private $child;

    /**
     * @param  mixed  $child
     */
    public function __construct($child)
    {
        if (!($child instanceof ExpressionInterface)) {
            $child = is_array($child) ? e\in($child) : e\eq($child);
        }

        $this->child = $child;

        parent::__construct('NOT (%s)', $this->child->getBinds());
    }

    /**
     * {@inheritDoc}
     */
    public function getExpr($column = null)
    {
        return parent::getExpr($this->child->getExpr($column));
    }
}
