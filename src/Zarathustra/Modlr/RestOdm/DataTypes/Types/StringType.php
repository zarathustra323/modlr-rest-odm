<?php

namespace Zarathustra\Modlr\RestOdm\DataTypes\Types;

/**
 * The string data type converter.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class StringType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function convertToModlrValue($value)
    {
        if (null !== $value) {
            return (String) $value;
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value)
    {
        if (null !== $value) {
            return (String) $value;
        }
        return null;
    }
}
