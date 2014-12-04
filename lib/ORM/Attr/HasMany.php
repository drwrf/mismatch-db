<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Attr;

class HasMany extends Relationship
{
    /**
     * {@inheritDoc}
     */
    public function isRelation($value)
    {
        return $value instanceof Query;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadRelation($model)
    {
        // We don't actually load the model, simply start the WHERE
        // that will lead to a successful load of the model.
        return $this->createQuery()->where([
            $this->fk() => $model->read($this->pk())
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function resolvePk()
    {
        return $this->metadata['pk']->name;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveFk()
    {
        $this->metadata['orm:fk'];
    }
}
