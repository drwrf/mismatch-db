<?php

namespace Mismatch\ORM\Attr;

use Mismatch\ORM\Exception\ModelNotFoundException;

class HasOne extends Relationship
{
    /**
     * {@inheritDoc}
     */
    public function isRelation($value)
    {
        return is_a($value, $this->className());
    }

    /**
     * {@inheritDoc}
     */
    protected function loadRelation($model)
    {
        $value = $model->read($this->pk());

        if (!$value && $this->nullable) {
            return null;
        }

        if (!$value) {
            throw new ModelNotFoundException($this->className(), $value);
        }

        $query = $this->createQuery();
        $filter = [$this->fk() => $value];

        return $this->nullable
            ? $query->first($filter)
            : $query->find($filter);
    }
}
