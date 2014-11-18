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

    /**
     * Turns a word into an underscore-separated string suitable
     * for use as a column name.
     *
     * @param  string  $word
     * @return string
     */
    public static function columnize($word)
    {
        return str_replace('\\', '_', parent::tableize($word));
    }
}
