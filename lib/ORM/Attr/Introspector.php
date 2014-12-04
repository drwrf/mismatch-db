<?php

namespace Mismatch\ORM\Attr;

use Mismatch\ORM\Inflector;
use Mismatch\ORM\Connection;
use Mismatch\Model\Attrs;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;

class Introspector
{
    /**
     * @var  AbstractSchemaManager
     */
    private $schema;

    /**
     * @var  string
     */
    private $table;

    /**
     * Constructor.
     *
     * @param   Connection  $conn
     * @param   string      $table
     */
    public function __construct($conn, $table)
    {
        $this->schema = $conn->getSchemaManager();
        $this->table = $table;
    }

    /**
     * Populates a set of attributes with those found
     * in the database.
     *
     * @param   Attrs  $attrs
     * @return  $this
     */
    public function populate($attrs)
    {
        foreach ($this->listColumns() as $key => $column) {
            $this->populateColumn($attrs, $key, $column);
        }

        foreach ($this->listIndexes() as $key => $index) {
            $this->populatePrimaryKey($attrs, $index);
        }

        return $this;
    }

    /**
     * Populates attributes.
     *
     * @param   Attrs   $attrs
     * @param   string  $key
     * @param   Column  $column
     */
    private function populateColumn($attrs, $key, $column)
    {
        $attrs->set($this->deriveName($key), [
            'key'      => $key,
            'type'     => $column->getType()->getName(),
            'default'  => $column->getDefault(),
            'nullable' => !$column->getNotnull(),
        ]);
    }

    /**
     * Populates a primary key based on a index.
     *
     * @param   Attrs  $attrs
     * @param   Index  $index
     */
    private function populatePrimaryKey($attrs, $index)
    {
        if (!$index->isPrimary()) {
            return;
        }

        $columns = $index->getColumns();
        $key = current($columns);

        if (count($columns) > 1) {
            throw new RuntimeException(
                'Composite primary keys are not supported.');
        }

        $attrs->set($this->deriveName($key), [
            'key' => $key,
            'type' => 'Primary',
        ]);
    }

    /**
     * @param  string  $name
     */
    private function deriveName($key)
    {
        return Inflector::camelize($key);
    }

    /**
     * @return  array
     */
    private function listColumns()
    {
        return $this->schema->listTableColumns($this->table);
    }

    /**
     * @return  array
     */
    private function listIndexes()
    {
        return $this->schema->listTableIndexes($this->table);
    }
}
