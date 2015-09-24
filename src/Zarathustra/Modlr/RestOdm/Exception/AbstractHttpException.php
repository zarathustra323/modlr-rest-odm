<?php

namespace Zarathustra\Modlr\RestOdm\Exception;

/**
 * AbstractHttpException.
 * Can be extended to provide support for HTTP friendly response codes.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
abstract class AbstractHttpException extends \Exception implements HttpExceptionInterface
{
    /**
     * The HTTP response code.
     *
     * @param int
     */
    protected $httpCode;

    /**
     * Constructor.
     * Overwritten to require a message and an HTTP code.
     *
     * @param   string                          $message
     * @param   int                             $httpCode
     * @param   int                             $code
     * @param   HttpExceptionInterface|null     $previous
     */
    public function __construct($message, $httpCode, $code = 0, HttpExceptionInterface $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->httpCode = (Integer) $httpCode;
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }
}
