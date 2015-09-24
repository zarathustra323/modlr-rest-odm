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
     * @return  array
     */
    public function serializeError($message, $httpCode);

    public function normalize(RestPayload $payload);

    /**
     * Serializes a Struct\Resource object into a Rest\RestPayload object
     *
     * @param   Struct\Resource     $resource
     * @param   RestPayload
     */
    public function serialize(Struct\Resource $resource, AdapterInterface $adapter);

    public function serializeIdentifier(Struct\Identifier $identifier, AdapterInterface $adapter);

    public function serializeEntity(Struct\Entity $entity, AdapterInterface $adapter);

    public function serializeCollection(Struct\Collection $collection, AdapterInterface $adapter);
}
