<?php

namespace Zarathustra\Modlr\RestOdm\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zarathustra\Modlr\RestOdm\Adapter\AdapterInterface;
use Zarathustra\Modlr\RestOdm\Serializer\ErrorSerializerInterface;
use Zarathustra\Modlr\RestOdm\Exception\HttpExceptionInterface;

/**
 * REST Kernel for handling incoming Requests, converting them to REST format, and handling API actions.
 *
 * @todo    CORS would need to be implemented by account
 * @todo    Account API key would need to be parsed in order to establish Org and Project
 * @todo    Support for multiple APIs (persistence vs. search)
 *
 * @author  Jacob Bare <jbare@southcomm.com>
 */
class RestKernel
{
    private $adapter;

    private $config;

    public function __construct(AdapterInterface $adapter, RestConfiguration $config)
    {
        $this->adapter = $adapter;
        $this->config = $config;
    }

    /**
     * Processes an incoming Request object, routes it to the appropriate adapter method, and returns a response.
     *
     * @todo    Needs to handle exceptions and also implement a response friendly exception interface for http codes and the like.
     * @param   Request     $request
     * @return  Response    $response
     */
    public function handle(Request $request)
    {
        // @todo Need to validate the core Request object. Ensure JSON, etc.

        try {
            $restRequest = new RestRequest($this->config, $request->getMethod(), $request->getUri(), $request->getContent());
            $restResponse = $this->adapter->processRequest($restRequest);
        } catch (\Exception $e) {
            // throw $e;
            $restResponse = $this->adapter->handleException($e);
        }
        return new Response($restResponse->getContent(), $restResponse->getStatus(), $restResponse->getHeaders());
    }
}
