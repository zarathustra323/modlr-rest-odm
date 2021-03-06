<?php

namespace Zarathustra\Modlr\RestOdm\Rest;

/**
 * REST Payload object wrapper.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class RestPayload
{
    /**
     * The payload contents.
     *
     * @var string
     */
    private $data;

    /**
     * Constructor.
     *
     * @param   string  $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Gets the payload contents.
     *
     * @return  string
     */
    public function getData()
    {
        return $this->data;
    }
}
