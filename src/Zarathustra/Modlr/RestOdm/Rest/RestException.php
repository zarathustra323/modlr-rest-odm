<?php

namespace Zarathustra\Modlr\RestOdm\Rest;

use Zarathustra\Modlr\RestOdm\Exception\AbstractHttpException;

/**
 * Rest Request exceptions.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class RestException extends AbstractHttpException
{
    public static function invalidEndpoint($path)
    {
        return new self(
            sprintf(
                'The provided path "%s" is not a valid API endpoint.',
                $path
            ),
            400
        );
    }

    public static function invalidRelationshipEndpoint($path)
    {
        return new self(
            sprintf(
                'The provided path "%s" is not a valid relationship API endpoint.',
                $path
            ),
            400
        );
    }

    public static function invalidRequestType($type, $supported)
    {
        return new self(
            sprintf(
                'The API request type "%s" is invalid. Valid request types are: "%s"',
                $type,
                implode(', ', $supported)
            ),
            400
        );
    }

    public static function unsupportedQueryParam($param, array $supported)
    {
        return new self(
            sprintf(
                'The query parameter "%s" is not supported. Supported parameters are "%s"',
                $param,
                implode(', ', $supported)
            ),
            400
        );
    }

    public static function invalidQueryParam($param, $message = null)
    {
        return new self(
            sprintf(
                'The query parameter "%s" is invalid. %s',
                $param,
                $message
            ),
            400
        );
    }

    public static function noAdapterFound($requestType)
    {
        return new self(
            sprintf(
                'No REST adapter was found to handle "%s" requests.',
                $requestType
            ),
            400
        );
    }
}
