<?php

namespace Zarathustra\Modlr\RestOdm\Store;

use Zarathustra\Modlr\RestOdm\Exception\AbstractHttpException;

/**
 * Store exceptions.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class StoreException extends AbstractHttpException
{
    public static function recordNotFound($type, $identifer)
    {
        return new self(
            sprintf(
                'No record was found for "%s" using id "%s"',
                $type,
                $identifer
            ),
            404,
            __FUNCTION__
        );
    }

    public static function invalidInclude($type, $fieldKey)
    {
        return new self(
            sprintf(
                'The relationship key "%s" was not found on entity "%s"',
                $fieldKey,
                $type
            ),
            400,
            __FUNCTION__
        );
    }
}
