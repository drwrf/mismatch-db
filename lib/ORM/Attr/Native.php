<?php

/**
 * This file is part of Mismatch.
 *
 * @author   ♥ <hi@drwrf.com>
 * @license  MIT
 */
namespace Mismatch\ORM\Attr;

use Mismatch\Model\Attr\Primitive;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Native extends Primitive
{
    /**
     * @var  Type  The underlying doctrine type.
     */
    protected $type;

    /**
     * @var  AbstractPlatform  The platform to use for type conversion.
     */
    protected $platform;

    /**
     * {@inheritDoc}
     */
    public function castToPHP($value)
    {
        return $this->type->convertToPHPValue($value, $this->getPlatform());
    }

    /**
     * {@inheritDoc}
     */
    public function castToNative($value)
    {
        return $this->type->convertToDatabaseValue($value, $this->getPlatform());
    }

    /**
     * @return  AbstractPlatform
     */
    private function getPlatform()
    {
        if (!$this->platform) {
            $this->platform = $this->metadata['orm:connection']->getDatabasePlatform();
        }

        return $this->platform;
    }
}
