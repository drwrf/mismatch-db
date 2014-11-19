<?php

namespace Mismatch\ORM\Exception;

class UndestroyableModelException extends ORMException
{
    public function __construct($model)
    {
        parent::__construct(sprintf(
            "Could not destroy '%s'. Either there was no model to destroy " .
            "or you destroyed more than one model. Are you sure the model " .
            "was actually saved to the database in the first place?",
            $model));
    }
}
