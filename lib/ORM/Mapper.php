<?php

namespace Mismatch\ORM;

use Mismatch\Model\Attrs;
use Mismatch\Model\Metadata;
use Mismatch\Model\Dataset;
use Mismatch\Model\Attr\AttrInterface;

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

        return $this->createModel($dataset);
    }

    /**
     * Hook for creating a new model.
     *
     * @param   Dataset  $dataset
     * @return  object
     */
    protected function createModel($dataset)
    {
        $class = $this->metadata->getClass();

        return new $class($dataset);
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
