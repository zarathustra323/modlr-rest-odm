<?php

namespace Zarathustra\Modlr\RestOdm\Rest;

/**
 * Object wrapper for REST request payloads.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class RestPayload
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
