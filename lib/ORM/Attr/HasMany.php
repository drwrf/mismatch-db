<?php

namespace Mismatch\ORM\Attr;

class HasMany extends Relationship
{
    /**
     * {@inheritDoc}
     */
    public function isValid($value)
    {
        return $value instanceof Query;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadRelationship($model)
    {
        $query = $this->foreignMeta()['orm:query'];
        $value = $model->read($this->pk());

        // We don't actually load the model, simply start the WHERE
        // that will lead to a successful load of the model.
        return $query->where([
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
