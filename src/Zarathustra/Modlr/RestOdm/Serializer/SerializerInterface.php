<?php

namespace Zarathustra\Modlr\RestOdm\Serializer;

use Zarathustra\Modlr\RestOdm\Adapter\AdapterInterface;
use Zarathustra\Modlr\RestOdm\Rest\RestPayload;
use Zarathustra\Modlr\RestOdm\Struct;

/**
 * Interface for serializing resources and normalizing payloads in the implementing format.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
interface SerializerInterface
{
    /**
     * Gets a serialized error response.
     *
     * @return  string
     */
    public function serializeError($message, $httpCode);

    /**
     * Normalizes a RestPayload into a Struct\Resource.
     *
     * @param   RestPayload     $payload
     * @return  Struct\Resource
     */
    public function normalize(RestPayload $payload);

    /**
     * Serializes a Struct\Resource object into a Rest\RestPayload object
     *
     * @param   Struct\Resource     $resource
     * @param   AdapterInterface    $adapter
     * @param   RestPayload
     */
    public function serialize(Struct\Resource $resource, AdapterInterface $adapter);

    /**
     * Serializes a Struct\Identifier object into the appropriate format.
     *
     * @param   Struct\Identifier   $identifer
     * @param   AdapterInterface    $adapter
     * @return  array
     */
    public function serializeIdentifier(Struct\Identifier $identifier, AdapterInterface $adapter);

    /**
     * Serializes a Struct\Entity object into the appropriate format.
     *
     * @param   Struct\Entity       $entity
     * @param   AdapterInterface    $adapter
     * @return  array
     */
    public function serializeEntity(Struct\Entity $entity, AdapterInterface $adapter);

    /**
     * Serializes a Struct\Collection object into the appropriate format.
     *
     * @param   Struct\Collection   $collection
     * @param   AdapterInterface    $adapter
     * @return  array
     */
    public function serializeCollection(Struct\Collection $collection, AdapterInterface $adapter);
}
