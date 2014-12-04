<?php

namespace Mismatch\ORM\Exception;

class InvalidRelationshipException extends ORMException
{
    public function __construct($model, $attr, $value)
    {
        parent::__construct(sprintf(
            "You tried to set the invalid value '%s' for the '%s' relation ".
            "on the '%s' model. In general, relationships only support ".
            "assignment with collections, queries, and individual models.",
            $value, $attr, $model));
    }
}
