<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM;

use Doctrine\DBAL\Cache\QueryCacheProfile as QCP;
use Doctrine\DBAL\Connection as Base;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use DateTime;
use PDO;

/**
 * Handles connecting to the database.
 *
 * In general, you should not need to manually create instances of this
 * class. Instead, you should pass an array of configuration to the static
 * method `create`, which will handle building up a connection for you.
 *
 * As an example, let's take a look at connecting to an in-memory instance
 * of SQLite.
 *
 * ```php
 * $conn = Connection::create([
 *   'driver' => 'pdo_sqlite',
 *   'memory' => true,
 * ]);
 * ```
 *
 * You can easily connect to all of the databases that Doctrine's DBAL supports.
 * As such, you can refer to the [Doctrine\DBAL][dbal-docs] documentation
 * for the specifics on connecting to various databases.
 *
 * [dbal-docs]: http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html
 * @link http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html
 */
class Connection extends Base
{
    /**
     * @var  array  A pool of connections, shared across all models.
     */
    private static $pool = [];

    /**
     * Returns a connection based on the configuration passed.
     *
     * If a connection has already been made using the configuration
     * then that same instance will be returned.
     *
     * @param   array  $config  The config to use for connecting to the DB
     * @return  Connection
     * @api
     */
    public static function create(array $config)
    {
        // Override Doctrine's connection class so we can change it if necessary.
        if (empty($config['wrapperClass'])) {
            $config['wrapperClass'] = get_called_class();
        }

        // We use the configuration to determine if a connection is unique,
        // which allows sharing connections across models.
        ksort($config);
        $key = json_encode($config);

        if (empty(self::$pool[$key])) {
            self::$pool[$key] = DriverManager::getConnection($config);
        }

        return self::$pool[$key];
    }

    /**
     * Resets the connection pool.
     *
     * @api
     */
    public static function reset()
    {
        self::$pool = [];
    }

    /**
     * {@inheritDoc}
     */
    public function executeQuery($query, array $params = [], $types = [], QCP $qcp = null)
    {
        // Allow passing nothing for the type information so we can
        // figure it out for the caller. Often, this is better.
        if (!$types && $params) {
            $types = $this->prepareTypes($params);
        }

        return parent::executeQuery($query, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        // Allow passing nothing for the type information so we can
        // figure it out for the caller. Often, this is better.
        if (!$types && $params) {
            $types = $this->prepareTypes($params);
        }

        return parent::executeUpdate($query, $params, $types);
    }

    /**
     * Creates a list of types from a list of parameters, so
     * that PDO can properly translate the value for the RDBMS.
     *
     * @param   array  $params  A list of params to detect types on.
     * @return  array
     */
    public function prepareTypes(array $params)
    {
        $types = [];

        foreach ($params as $key => $value) {
            $types[$key] = $this->detectType($value);
        }

        return $types;
    }

    /**
     * Attempts to detect the doctrine type of a particular value.
     *
     * @param  mixed  $value
     */
    private function detectType($value)
    {
        if (is_integer($value)) {
            return Type::INTEGER;
        }

        if (is_bool($value)) {
            return Type::BOOLEAN;
        }

        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        if (is_array($value)) {
            return is_integer(current($value))
                ? Connection::PARAM_INT_ARRAY
                : Connection::PARAM_STR_ARRAY;
        }

        if ($value instanceof DateTime) {
            return Type::DATETIME;
        }

        return PDO::PARAM_STR;
    }
}
