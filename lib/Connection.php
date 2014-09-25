<?php

namespace Mismatch\DB;

use Doctrine\DBAL\Connection as Base;
use Doctrine\DBAL\DriverManager;

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
 * <code>
 * $conn = Connection::create([
 *   'driver' => 'pdo_sqlite',
 *   'memory' => true,
 * ]);
 * </code>
 *
 * Mismatch\DB supports all of the databases that Doctrine's DBAL supports.
 * As such, you can refer to the Doctrine\DBAL documentation for the specifics
 * on connecting to various databases.
 *
 * @see http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html
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
     * @param   array  $config
     * @return  Mismatch\DB\Connection
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
     */
    public static function reset()
    {
        self::$pool = [];
    }
}
