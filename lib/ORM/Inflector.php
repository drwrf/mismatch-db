<?php

namespace Mismatch\ORM;

use Doctrine\Common\Inflector\Inflector as DoctrineInflector;

class Inflector extends DoctrineInflector
{
    /**
     * {@inheritDoc}
     */
    public static function tableize($word)
    {
        return static::pluralize(str_replace('\\', '_', parent::tableize($word)));
    }
}
