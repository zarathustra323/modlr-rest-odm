<?php

namespace Zarathustra\Modlr\RestOdm\Rest;

/**
 * REST Configuration.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class RestConfiguration
{
    // Should be determined by the server/version config
    const ROOT_ENDPOINT = '/api/1.0';
    const NS_DELIM_EXTERNAL = '_';
    const NS_DELIM_INTERNAL = '\\';

    private $scheme;

    private $host;

    public function getRootEndpoint()
    {
        return self::ROOT_ENDPOINT;
    }

    public function getInternalNamespaceDelim()
    {
        return self::NS_DELIM_INTERNAL;
    }

    public function getExternalNamespaceDelim()
    {
        return self::NS_DELIM_EXTERNAL;
    }

    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function getHost()
    {
        return $this->host;
    }
}
