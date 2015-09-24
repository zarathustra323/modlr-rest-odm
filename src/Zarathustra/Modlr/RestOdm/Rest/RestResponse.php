<?php

namespace Zarathustra\Modlr\RestOdm\Rest;

use Zarathustra\Modlr\RestOdm\Exception\InvalidArgumentException;

/**
 * Primary REST response object.
 * Is created by the API adapter.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class RestResponse
{
    private $status;

    private $payload;

    private $headers = [];

    public function __construct($status, RestPayload $payload = null)
    {
        $this->status = (Integer) $status;
        $this->payload = $payload;
    }

    public function addHeader($name, $value)
    {
        $name = strtolower($name);
        $this->headers[$name] = $value;
        return $this;
    }

    public function setHeaders(array $headers)
    {
        foreach ($this->headers as $name => $value) {
            $this->addHeader($name, $value);
        }
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getContent()
    {
        if (null === $this->getPayload()) {
            return null;
        }
        return $this->getPayload()->getData();
    }
}
