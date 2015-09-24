<?php

namespace Zarathustra\Modlr\RestOdm\Exception;

/**
 * HTTP Response friendly Exception interface.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
interface HttpExceptionInterface extends ExceptionInterface
{
    /**
     * Gets the HTTP response code.
     *
     * @return  int
     */
    public function getHttpCode();
}
