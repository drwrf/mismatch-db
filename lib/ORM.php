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
        $m['table'] = function($m) {
            return Inflector::tableize($m->getClass());
        };

        // The primary key of the model, as an attribute.
        $m['pk'] = function($m) {
            foreach ($m['attrs'] as $attr) {
                if ($attr instanceof Primary) {
                    return $attr;
                }
            }

            throw new DomainException();
        };

        // The default foreign key of the model, by name.
        $m['fk'] = function($m) {
            return $m['table'] . '_id';
        };

        // The connection the model will use to talk to the database.
        $m['connection'] = $m->factory(function ($m) {
            return $m['connection:class']::create($m['credentials']);
        });

        // The class to use for this model's connection.
        $m['connection:class'] = 'Mismatch\ORM\Connection';

        // The query builder used for finding and modifying data
        $m['query'] = $m->factory(function($m) {
            $query = new $m['query:class']($m['connection'], $m['table'], $m['pk']);

            return $query;
        });

        // The class to use for query building.
        $m['query:class'] = 'Mismatch\ORM\Query';
    }
}
