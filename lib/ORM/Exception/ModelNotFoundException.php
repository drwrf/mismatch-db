<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Exception;

class ModelNotFoundException extends ORMException
{
    public function __construct($model, $filter)
    {
        if (is_array($filter)) {
            $filter = json_encode($filter);
        }

        parent::__construct(sprintf(
            "Could not find model for '%s' with '%s'.",
            $model, $filter));
    }
}
