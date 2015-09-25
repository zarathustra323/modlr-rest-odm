<?php

namespace Zarathustra\Modlr\RestOdm\Adapter;

use Zarathustra\Modlr\RestOdm\Exception\MetadataException;
use Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata;
use Zarathustra\Modlr\RestOdm\Metadata\MetadataFactory;
use Zarathustra\Modlr\RestOdm\Store\StoreInterface;
use Zarathustra\Modlr\RestOdm\Serializer\JsonApiSerializer;
use Zarathustra\Modlr\RestOdm\Rest;
use Zarathustra\Modlr\RestOdm\Struct;
use Zarathustra\Modlr\RestOdm\Util\Inflector;
use Zarathustra\Modlr\RestOdm\Exception\HttpExceptionInterface;

/**
 * Adapter for handling API operations using the JSON API specification.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class JsonApiAdapter implements AdapterInterface
{
    /**
     * The Modlr Metadata factory.
     *
     * @var MetadataFactory
     */
    private $mf;

    /**
     * The JsonApiSerializer
     *
     * @var JsonApiSerializer
     */
    private $serializer;

    /**
     * The Store to use for persistence operations.
     *
     * @var StoreInterface
     */
    private $store;

    /**
     * @var Inflector
     */
    private $inflector;

    /**
     * The REST configuration.
     *
     * @var Rest\RestConfiguration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param   MetadataFactory         $mf
     * @param   JsonApiSerializer       $serializer
     * @param   StoreInterface          $store
     * @param   Rest\RestConfiguration  $config
     */
    public function __construct(MetadataFactory $mf, JsonApiSerializer $serializer, StoreInterface $store, Rest\RestConfiguration $config)
    {
        $this->mf = $mf;
        $this->serializer = $serializer;
        $this->store = $store;
        $this->config = $config;
        $this->inflector = new Inflector();
    }

    /**
     * {@inheritDoc}
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * {@inheritDoc}
     */
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
                if (true === $request->hasIdentifier()) {
                    if (false === $request->isRelationship() && false === $request->hasFilters()) {
                        return $this->findRecord($metadata, $request->getIdentifier(), $request->getFieldset(), $request->getInclusions());
                    }
                } else {
                    return $this->findMany($metadata, [], $request->getPagination(), $request->getFieldset(), $request->getInclusions(), $request->getSorting());
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

    /**
     * {@inheritDoc}
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = [])
    {
        $resource = $this->getStore()->findRecord($metadata, $identifier, $fields, $inclusions);
        $payload = $this->serialize($resource);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = [])
    {
        $resource = $this->getStore()->findMany($metadata, $identifiers, $pagination, $fields, $inclusions, $sort);
        $payload = $this->serialize($resource);
        return $this->createRestResponse(200, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function handleException(\Exception $e)
    {
        if ($e instanceof HttpExceptionInterface) {
            $refl = new \ReflectionClass($e);
            $title  = sprintf('%s::%s', $refl->getShortName(), $e->getErrorType());
            $detail = $e->getMessage();
            $status = $e->getHttpCode();
        } else {
            $title  = 'Exception';
            $detail = 'An internal server error occured';
            $status = 500;
        }

        $serialized = $this->getSerializer()->serializeError($title, $detail, $status);
        $payload = new Rest\RestPayload($serialized);
        return $this->createRestResponse($status, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function getInternalEntityType($externalType)
    {
        $parts = explode($this->config->getExternalNamespaceDelim(), $externalType);
        foreach ($parts as &$part) {
            $part = $this->inflector->studlify($part);
        }
        return implode($this->config->getInternalNamespaceDelim(), $parts);
    }

    /**
     * {@inheritDoc}
     */
    public function getExternalEntityType($internalType)
    {
        $parts = explode($this->config->getInternalNamespaceDelim(), $internalType);
        foreach ($parts as &$part) {
            $part = $this->inflector->dasherize($part);
        }
        return implode($this->config->getExternalNamespaceDelim(), $parts);
    }

    /**
     * {@inheritDoc}
     */
    public function getExternalFieldKey($internalKey)
    {
        return $this->inflector->dasherize($internalKey);
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * Creates a RestResponse object based on common response parameters shared by this adapter.
     *
     * @param   int                 $status
     * @param   Rest\RestPayload    $payload
     * @return  Rest\RestResponse
     */
    protected function createRestResponse($status, Rest\RestPayload $payload)
    {
        $restResponse = new Rest\RestResponse($status, $payload);
        $restResponse->addHeader('content-type', 'application/json');
        return $restResponse;
    }
}
