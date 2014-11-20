<?php

namespace Mismatch\ORM\Exception;

class ModelNotFoundException extends ORMException
{
    public function __construct($model, $id)
    {
        parent::__construct(sprintf(
            "Could not find model for '%s' with '%s'.",
            $model, $id));
    }
}
