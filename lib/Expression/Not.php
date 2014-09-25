<?php

namespace Mismatch\SQL\Expression;

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
        if (!($child instanceof Expression)) {
            $child = is_array($child) ? new In($child) : new Eq($child);
        }

        parent::__construct('NOT (%s)', $child->getBinds());
    }

    /**
     * {@inheritDoc}
     */
    public function getExpr($column = null)
    {
        return parent::getExpr($this->child->getExpr($column));
    }
}
