<?php

namespace Mismatch\ORM\Attr;

use Mismatch\ORM\Inflector;
use Mismatch\Model\Attr\AttrInterface;
use Mismatch\ORM\Exception\ModelNotFoundException;

class BelongsTo extends Relationship
{
    /**
     * {@inheritDoc}
     */
    protected $serialize = AttrInterface::SERIALIZE_PRE_PERSIST;

    /**
     * {@inheritDoc}
     */
    public function __construct($name, array $opts = [])
    {
        parent::__construct($name, $opts);

        if (!$this->key || $this->key === $this->name) {
            $this->key = $this->resolveFk();
        }
    }

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
        $value = $model->read($this->fk());

        if (!$value && $this->nullable) {
            return null;
        }

        if (!$value) {
            throw new ModelNotFoundException($this->className(), $value);
        }

        // Use the primary key only if it's declared. We can trust
        // the query class to use the right foreign key if not.
        if ($pk = $this->pk()) {
            $value = [$pk => $value];
        }

        return $this->nullable
            ? $this->createQuery()->first($value)
            : $this->createQuery()->find($value);
    }

    /**
     * {@inheritDoc}
     */
    protected function resolvePk()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveFk()
    {
        return $this->name . '_id';
    }
}
