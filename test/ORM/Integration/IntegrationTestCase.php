<?php

namespace Mismatch\ORM\Integration;

use Mismatch\Model\Metadata;
use Doctrine\DBAL\Schema\Table;

trait IntegrationTestCase
{
    /**
     * @param  string  $model
     */
    private function createTable($model, array $columns)
    {
        $metadata = Metadata::get($model);

        // Give the model a chance to modify the table for itself
        $table = new Table($metadata['orm:table']);

        foreach ($columns as $name => $options) {
            // Allow passing a type alone
            if (is_string($options)) {
                $options = ['type' => $options];
            }

            // Extract the type out of the options.
            $type = $options['type'];
            unset($options['type']);

            $column = $table->addColumn($name, $type, $options);

            if (!empty($options['primary'])) {
                $table->setPrimaryKey([$name]);
            }
        }

        // Now that the model has run its callback, create the table.
        $metadata['orm:connection']
            ->getSchemaManager()
            ->createTable($table);
    }

    /**
     * @param  string  $model
     */
    private function seedTable($model, array $data)
    {
        $metadata = Metadata::get($model);

        foreach ($data as $row) {
            $metadata['orm:query']->insert($row);
        }
    }
}
