<?php

namespace Mismatch\ORM;

use Mismatch\Model\Attrs;
use Mismatch\Model\Dataset;
use Mismatch\Model\Attr\AttrInterface;

class Mapper
{
    /**
     * @var  string
     */
    private $class;

    /**
     * @var  Attrs
     */
    private $attrs;

    /**
     * Constructor.
     *
     * @param  string  $class
     * @param  Attrs   $attrs
     */
    public function __construct($class, $attrs)
    {
        $this->class = $class;
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

        return new $this->class($dataset);
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
