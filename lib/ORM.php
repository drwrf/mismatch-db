<?php

namespace Mismatch;

/**
 * This Mismatch trait allows a model to interact with a database.
 */
trait ORM
{
    /**
     * Installs the ORM-related functionality on a model.
     *
     * @param  Model\Metadata  $m
     */
    public static function usingORM($m)
    {
        // The table that we want to connect the model to.
        $m['orm:table'] = function($m) {
            return ORM\Inflector::tableize($m->getClass());
        };

        // The primary key of the model, as an attribute.
        $m['orm:pk'] = function($m) {
            foreach ($m['attrs'] as $attr) {
                if ($attr instanceof Primary) {
                    return $attr;
                }
            }

            throw new DomainException();
        };

        // The default foreign key of the model, by name.
        $m['orm:fk'] = function($m) {
            return $m['orm:table'] . '_id';
        };

        // The connection the model will use to talk to the database.
        $m['orm:connection'] = $m->factory(function ($m) {
            return $m['orm:connection:class']::create($m['orm:credentials']);
        });

        // The class to use for this model's connection.
        $m['orm:connection:class'] = 'Mismatch\ORM\Connection';

        // The query builder used for finding and modifying data
        $m['orm:query'] = $m->factory(function($m) {
            $query = new $m['orm:query:class'](
                $m['orm:connection'],
                $m['orm:table'],
                $m['orm:pk']);

            return $query;
        });

        // The class to use for query building.
        $m['orm:query:class'] = 'Mismatch\ORM\Query';
    }
}
