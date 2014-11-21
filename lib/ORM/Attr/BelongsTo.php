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

        // BelongsTo behaves a little bit differently from other
        // relationships. As it actually represents a real column
        // in the database so we should afford that.
        $this->key = $this->resolveFk();
    }

    /**
     * {@inheritDoc}
     */
    public function isValid($value)
    {
        return is_a($value, $this->foreignMeta()->getClass());
    }

    /**
     * {@inheritDoc}
     */
    protected function loadRelationship($model)
    {
        $value = $model->read($this->fk());
        $meta  = $this->foreignMeta();
        $query = $meta['orm:query'];

        if (!$value && $this->nullable) {
            return null;
        }

        if (!$value) {
            throw new ModelNotFoundException($meta->getClass(), $value);
        }

        // Use the primary key only if it's declared. We can trust
        // the query class to use the right foreign key if not.
        if ($pk = $this->pk()) {
            $value = [$pk => $value];
        }

        return $this->nullable
            ? $query->first($value)
            : $query->find($value);
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
