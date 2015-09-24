<?php

namespace Zarathustra\Modlr\RestOdm\Adapter;

use Zarathustra\Modlr\RestOdm\Exception\MetadataException;
use Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata;
use Zarathustra\Modlr\RestOdm\Metadata\MetadataFactory;
use Zarathustra\Modlr\RestOdm\Store\StoreInterface;
use Zarathustra\Modlr\RestOdm\Serializer\SerializerInterface;
use Zarathustra\Modlr\RestOdm\Rest;
use Zarathustra\Modlr\RestOdm\Struct;
use Zarathustra\Modlr\RestOdm\Util\Inflector;
use Zarathustra\Modlr\RestOdm\Exception\HttpExceptionInterface;

/**
 * Interface for handling API operations using Json Api Spec.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class JsonApiAdapter implements AdapterInterface
{
    private $mf;

    private $serializer;

    private $store;

    private $inflector;

    private $config;

    public function __construct(MetadataFactory $mf, SerializerInterface $serializer, StoreInterface $store, Rest\RestConfiguration $config)
    {
        $this->mf = $mf;
        $this->serializer = $serializer;
        $this->store = $store;
        $this->config = $config;
        $this->inflector = new Inflector();
    }

    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function processRequest(Rest\RestRequest $request)
    {
        $internalType = $this->getInternalEntityType($request->getEntityType());
        $metadata = $this->getEntityMetadata($internalType);
        switch ($request->getMethod()) {
            case 'GET':
                if (true === $request->hasIdentifier() && false === $request->isRelationship() && false === $request->hasFilters()) {
                    return $this->findRecord($metadata, $request->getIdentifier(), $request->getFieldset(), $request->getInclusions());
                }
                break;
            case 'POST':
                break;
            case 'PATCH':
                break;
            case 'DELETE':
                break;
            default:
                throw AdapterException::invalidRequestMethod($request->getMethod());
                break;
        }
        var_dump(__METHOD__, $request);
        die();
    }

    public function handleException(\Exception $e)
    {
        if ($e instanceof HttpExceptionInterface) {
            $message = $e->getMessage();
            $status = $e->getHttpCode();
        } else {
            $message = 'An internal server error occured';
            $status = 500;
        }

        $serialized = $this->getSerializer()->serializeError($message, $status);
        $payload = new Rest\RestPayload($serialized);
        return $this->createRestResponse($status, $payload);
    }

    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = [])
    {
        $resource = $this->getStore()->findRecord($metadata, $identifier, $fields, $inclusions);
        var_dump($resource);
        die();
        $payload = $this->serialize($resource);
        return $this->createRestResponse(200, $payload);
    }

    protected function createRestResponse($status, Rest\RestPayload $payload)
    {
        $restResponse = new Rest\RestResponse($status, $payload);
        $restResponse->addHeader('content-type', 'application/json');
        return $restResponse;
    }

    public function getInternalEntityType($externalType)
    {
        $parts = explode($this->config->getExternalNamespaceDelim(), $externalType);
        foreach ($parts as &$part) {
            $part = $this->inflector->studlify($part);
        }
        return implode($this->config->getInternalNamespaceDelim(), $parts);
    }

    public function getExternalEntityType($internalType)
    {
        $parts = explode($this->config->getInternalNamespaceDelim(), $internalType);
        foreach ($parts as &$part) {
            $part = $this->inflector->dasherize($part);
        }
        return implode($this->config->getExternalNamespaceDelim(), $parts);
    }

    public function getExternalFieldKey($internalKey)
    {
        return $this->inflector->dasherize($internalKey);
    }

    public function buildUrl(EntityMetadata $metadata, $identifier, $relFieldKey = null, $isRelatedLink = false)
    {
        $externalType = $this->getExternalEntityType($metadata->type);

        $url = sprintf('%s://%s%s/%s/%s',
            $this->config->getScheme(),
            $this->config->getHost(),
            $this->config->getRootEndpoint(),
            $this->getExternalEntityType($metadata->type),
            $identifier
        );

        if (null !== $relFieldKey) {
            if (false === $isRelatedLink) {
                $url .= '/relationships';
            }
            $url .= sprintf('/%s', $this->getExternalFieldKey($relFieldKey));
        }
        return $url;
    }

    public function getStore()
    {
        return $this->store;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(Rest\RestPayload $payload)
    {
        var_dump(__METHOD__);
        die();
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(Struct\Resource $resource)
    {
        return new Rest\RestPayload($this->getSerializer()->serialize($resource, $this));
    }

    public function getEntityMetadata($internalType)
    {
        try {
            return $this->mf->getMetadataForType($internalType);
        } catch (MetadataException $e) {
            if (100 === $e->getCode()) {
                throw AdapterException::entityTypeNotFound($internalType);
            }
            throw $e;
        }
    }
}
