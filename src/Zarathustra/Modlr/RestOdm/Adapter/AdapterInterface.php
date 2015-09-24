<?php

namespace Zarathustra\Modlr\RestOdm\Adapter;

use Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata;
use Zarathustra\Modlr\RestOdm\Rest;
use Zarathustra\Modlr\RestOdm\Struct;

/**
 * Interface for handling API operations
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
interface AdapterInterface
{
    /**
     * Processes REST requests and formats them into REST responses.
     *
     * @param   Rest\RestRequest     $request
     * @return  Rest\RestResponse
     */
    public function processRequest(Rest\RestRequest $request);

    /**
     * Finds a single entity by id.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $fields
     * @param   array           $inclusions
     * @return  Rest\RestPayload
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = []);

    public function handleException(\Exception $e);

    public function getInternalEntityType($externalType);

    public function getExternalEntityType($internalType);

    public function getExternalFieldKey($internalKey);

    public function getStore();

    public function getSerializer();

    public function getEntityMetadata($type);

    public function buildUrl(EntityMetadata $metadata, $identifier, $externalRelKey = null, $isRelatedLink = false);

    /**
     * Normalizes a Rest\RestPayload into a Struct\Resource object.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Rest\RestPayload    $payload
     * @return  Struct\Resource
     */
    public function normalize(Rest\RestPayload $payload);

    /**
     * Serializes a Struct\Resource into a Rest\RestPayload object.
     * Is used in conjunction with a SerializerInterface.
     *
     * @param   Struct\Resource     $resource
     * @return  Rest\RestPayload
     */
    public function serialize(Struct\Resource $resource);
}
