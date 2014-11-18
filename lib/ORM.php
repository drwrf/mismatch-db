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
     * @param  Mismatch\Model\Metadata  $m
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
            return $m['orm:connection_class']::create($m['orm:credentials']);
        });

        // The class to use for this model's connection.
        $m['orm:connection_class'] = 'Mismatch\ORM\Connection';

        // The query builder used for finding and modifying data
        $m['orm:query'] = $m->factory(function($m) {
            $query = new $m['orm:query_class'](
                $m['orm:connection'],
                $m['orm:table'],
                $m['orm:pk']);

            $query->setStrategy([$m['orm:mapper'], 'hydrate']);

            return $query;
        });

        // The class to use for query building.
        $m['orm:query_class'] = 'Mismatch\ORM\Query';

        // The mapper to user for mapping between the DB and PHP.
        $m['orm:mapper'] = function($m) {
            return new $m['orm:mapper_class']($m->getClass(), $m['attrs']);
        };

        // The class to use for mapping between the db and php.
        $m['orm:mapper_class'] = 'Mismatch\ORM\Mapper';
    }
}
