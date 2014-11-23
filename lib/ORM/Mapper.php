<?php

namespace Mismatch\ORM;

use Mismatch\Model\Attrs;
use Mismatch\Model\Metadata;
use Mismatch\Model\Dataset;
use Mismatch\Model\Attr\AttrInterface;
use Mismatch\ORM\Exception\UndestroyableModelException;

class Mapper
{
    /**
     * @var  Metadata
     */
    private $metadata;

    /**
     * @var  Attrs
     */
    private $attrs;

    /**
     * Constructor.
     *
     * @param  Metadata  $metadata
     * @param  Attrs     $attrs
     */
    public function __construct($metadata, $attrs)
    {
        $this->metadata = $metadata;
        $this->attrs = $attrs;
    }

    /**
     * Maps a database result to an instance of a Mismatch model.
     *
     * @param  array  $result
     * @return mixed
     */
    public function hydrate(array $result)
    {
        $dataset = new Dataset($result);

        foreach ($this->attrs as $attr) {
            $dataset->write($attr->name, $this->deserialize($dataset, $attr));
        }

        $dataset->markPersisted();

        return $this->hydrateModel($dataset);
    }

    /**
     * Persists a model.
     *
     * @param  mixed  $model
     */
    public function persist($model)
    {
        return $this->transactional(function($query) use ($model) {
            return $this->persistModel($query, $model);
        });
    }

    /**
     * Hook for persisting a model.
     *
     * @param   Query   $query
     * @param   mixed   $model
     * @return  object
     */
    protected function persistModel($query, $model)
    {
        $pk = null;
        $before = [];
        $after = [];
        $data = [];

        foreach ($this->attrs as $attr) {
            // Run through the various serialization strategies
            switch ($attr->serialize) {
                // We want to run a callback before the model is saved,
                // so return a closure that we'll run inside the transaction.
                case AttrInterface::SERIALIZE_PRE_PERSIST:
                    $before[] = $attr;
                    break;

                // We want to run a callback after the model is saved,
                // so return a closure that we'll run inside the transaction.
                case AttrInterface::SERIALIZE_POST_PERSIST:
                    $after[] = $attr;
                    break;

                case AttrInterface::SERIALIZE_PRIMARY:
                    $pk = $attr;
                    break;

                // We alway want to change the value, so place it on
                // the main set of data to be saved.
                case AttrInterface::SERIALIZE_VALUE:
                    $data[$attr->key] = $this->serializeAttr($model, $attr);
                    break;

                case AttrInterface::SERIALIZE_NONE:
                    break;

                default:
                    throw new UnexpectedValueException();
            }
        }

        // Run through all of the pre-queries.
        foreach ($before as $attr) {
            $value = $this->serializeAttr($model, $attr);

            if ($attr->key) {
                $data[$attr->key] = $value;
            }
        }

        $dataset = $model->dataset();

        if ($dataset->isPersisted()) {
            $query->where($model->id())
                  ->update($data);
        } else {
            $id = $query->insert($data);
            $dataset->write($pk->name, $id);
        }

        // Run through all of the post-queries.
        foreach ($after as $attr) {
            $this->serializeAttr($model, $attr);
        }

        $dataset->markPersisted();
    }

    /**
     * Serializes an attribute, returning whatever value
     * it returns.
     *
     * @param   mixed          $model
     * @param   AttrInterface  $attr
     * @return  mixed
     */
    private function serializeAttr($model, $attr)
    {
        $data = $model->dataset();
        $diff = $data->diff($attr->name);

        if (!$diff) {
            continue;
        }

        return $attr->serialize($model, $diff[0], $diff[1]);
    }

    /**
     * Hook for creating a new model.
     *
     * @param   Dataset  $dataset
     * @return  object
     */
    protected function hydrateModel($dataset)
    {
        $class = $this->metadata->getClass();

        return new $class($dataset);
    }


    /**
     * Destroys a model.
     *
     * @param  object  $model
     */
    public function destroy($model)
    {
        $this->transactional(function($query) use ($model) {
            return $this->destroyModel($query, $model);
        });
    }

    /**
     * Hook for destroying a model.
     *
     * This is run inside of a transaction, so any exceptions
     * thrown will cause it to roll back.
     *
     * @param  Query   $query
     * @param  object  $model
     */
    protected function destroyModel($query, $model)
    {
        $data = $model->dataset();

        if (!$data->isPersisted()) {
            throw new UndestroyableModelException($model);
        }

        $id = $model->id();

        if (!$id || $query->delete($id) !== 1) {
            throw new UndestroyableModelException($model);
        }

        $data->markDestroyed();
    }

    /**
     * @return  Query
     */
    private function createQuery()
    {
        return $this->metadata['query'];
    }

    /**
     * @param   Closure  $fn
     * @return  Query
     */
    private function transactional($fn)
    {
        return $this->metadata['orm:query']->transactional($fn);
    }

    /**
     * @param   Dataset        $dataset
     * @param   AttrInterface  $attr
     * @return  mixed
     */
    private function deserialize($dataset, $attr)
    {
        return $attr->deserialize($dataset, $dataset->read($attr->key));
    }
}
