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
            404
        );
    }
}
