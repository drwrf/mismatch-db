<?php

namespace Mismatch;

use Mismatch\ORM\Query;
use Mismatch\Model\Metadata;

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
        // Auto-detect attributes.
        $m->extend('attrs', function($attrs, $m) {
            $m['orm:attr_introspector']->populate($attrs);

            return $attrs;
        });

        // The table that we want to connect the model to.
        $m['orm:table'] = function($m) {
            return ORM\Inflector::tableize($m->getClass());
        };

        // The default foreign key of the model, by name.
        $m['orm:fk'] = function($m) {
            return ORM\Inflector::columnize($m->getClass()) . '_id';
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
                $m['pk']);

            $query->fetchAs([$m['orm:mapper'], 'hydrate']);

            return $query;
        });

        // The class to use for query building.
        $m['orm:query_class'] = 'Mismatch\ORM\Query';

        // The mapper to user for mapping between the DB and PHP.
        $m['orm:mapper'] = function($m) {
            return new $m['orm:mapper_class']($m, $m['attrs']);
        };

        // The class to use for mapping between the db and php.
        $m['orm:mapper_class'] = 'Mismatch\ORM\Mapper';

        // The attribute inspector used for determining types.
        $m['orm:attr_introspector'] = function ($m) {
            return new $m['orm:attr_introspector_class'](
                $m['orm:connection'], $m['orm:table']);
        };

        $m['orm:attr_introspector_class'] = 'Mismatch\ORM\Attr\Introspector';
    }

    /**
     * Allows calling static methods on the class as if
     * they were the start of query chains.
     *
     * @param   string  $method
     * @param   array   $args
     * @return  Query
     */
    public static function __callStatic($method, array $args)
    {
        $query = Metadata::get(get_called_class())['orm:query'];

        return call_user_func_array([$query, $method], $args);
    }

    /**
     * Saves the model.
     *
     * @throws  Exception
     */
    public function persist()
    {
        $this->metadata['orm:mapper']->persist($this);
    }

    /**
     * Destroys the model.
     *
     * @throws  Exception
     */
    public function destroy()
    {
        $this->metadata['orm:mapper']->destroy($this);
    }
}
