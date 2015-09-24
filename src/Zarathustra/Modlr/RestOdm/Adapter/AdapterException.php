<?php

namespace Zarathustra\Modlr\RestOdm\Adapter;

use Zarathustra\Modlr\RestOdm\Exception\AbstractHttpException;

/**
 * Adapter exceptions.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class AdapterException extends AbstractHttpException
{
    public static function entityTypeNotFound($type)
    {
        return new self(
            sprintf(
                'No API resource was found for entity type "%s"',
                $type
            ),
            404
        );
    }
}
