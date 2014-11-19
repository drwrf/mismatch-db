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
