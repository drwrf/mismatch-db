<?php

namespace Mismatch\ORM\Attr;

use Mismatch\Model\Attr\Attr;
use Mismatch\Model\Attr\AttrInterface;
use Mismatch\Model\Metadata;
use Mismatch\ORM\Exception\InvalidRelationshipException;

abstract class Relationship extends Attr
{
    /**
     * {@inheritDoc}
     */
    protected $serialize = AttrInterface::SERIALIZE_POST_PERSIST;

    /**
     * @var  string  The model we're relating to.
     */
    protected $class;

    /**
     * @var  string  The key on the owning side of the relationship.
     */
    protected $pk;

    /**
     * @var  string  The key on the foreign side of the relationship.
     */
    protected $fk;

    /**
     * {@inheritDoc}
     */
    public function __construct($name, array $opts = [])
    {
        // Alias each to model—this is prettier.
        if (empty($opts['class']) && !empty($opts['each'])) {
            $opts['class'] = $opts['each'];
        }

        parent::__construct($name, $opts);
    }

    /**
     * {@inheritDoc}
     */
    public function read($model, $value)
    {
        if (!$this->isRelation($value)) {
            $value = $this->loadRelation($model);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function write($model, $value)
    {
        if (!$this->isRelation($value)) {
            throw new InvalidRelationshipException(
                get_class($model), $this->name, $value);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize($model, $old, $new)
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize($result, $value)
    {
        return;
    }

    /**
     * Returns the owner key of the relationship.
     *
     * @return  string
     */
    public function pk()
    {
        if (!$this->pk) {
            $this->pk = $this->resolvePk();
        }

        return $this->pk;
    }

    /**
     * Returns the foreign key of the relationship.
     *
     * @return  string
     */
    public function fk()
    {
        if (!$this->fk) {
            $this->fk = $this->resolveFk();
        }

        return $this->fk;
    }

    /**
     * @return  Mismatch\ORM\Query
     */
    protected function createQuery()
    {
        return $this->foreignMeta()['orm:query'];
    }

    /**
     * @return  Mismatch\ORM\Query
     */
    protected function className()
    {
        return $this->foreignMeta()->getClass();
    }

    /**
     * @return  Metadata
     */
    protected function foreignMeta()
    {
        if (!$this->class instanceof Metadata) {
            $this->class = Metadata::get($this->class);
        }

        return $this->class;
    }

    /**
     * Hook called to determine whether or not the value
     * is a valid relationship.
     *
     * @param   mixed  $value
     * @return  mixed
     */
    abstract public function isRelation($value);

    /**
     * Hook called when no foreign model has been loaded yet.
     *
     * This should return a value that can be set on the owning
     * model and used by the caller.
     *
     * @param   Mismatch\Model  $model
     * @return  mixed
     */
    abstract protected function loadRelation($model);

    /**
     * Should attempt to figure out the owner key based on the
     * configuration passed to the attribute.
     *
     * @return  string
     */
    abstract protected function resolvePk();

    /**
     * Should attempt to figure out the foreign key based on the
     * configuration passed to the attribute.
     *
     * @return  string
     */
    abstract protected function resolveFk();
}
