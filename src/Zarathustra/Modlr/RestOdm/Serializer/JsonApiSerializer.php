<?php

namespace Zarathustra\Modlr\RestOdm\Serializer;

use Zarathustra\Modlr\RestOdm\Metadata\MetadataFactory;
use Zarathustra\Modlr\RestOdm\Metadata\AttributeMetadata;
use Zarathustra\Modlr\RestOdm\Metadata\RelationshipMetadata;
use Zarathustra\Modlr\RestOdm\DataTypes\TypeFactory;
use Zarathustra\Modlr\RestOdm\Rest\RestRequest;
use Zarathustra\Modlr\RestOdm\Rest\RestPayload;
use Zarathustra\Modlr\RestOdm\Struct;
use Zarathustra\Modlr\RestOdm\Exception\RuntimeException;
use Zarathustra\Modlr\RestOdm\Adapter\AdapterInterface;

class JsonApiSerializer implements SerializerInterface
{
    /**
     * The type factory.
     * Used for converting values to the API data type format.
     *
     * @var TypeFactory
     */
    private $typeFactory;

    /**
     * Denotes the current object depth of the serializer.
     *
     * @var int
     */
    private $depth = 0;

    /**
     * A stack of resources objects to include.
     *
     * @var Resource[]
     */
    private $toInclude = [];

    /**
     * Constructor.
     *
     * @param   TypeFactory     $typeFactory
     */
    public function __construct(TypeFactory $typeFactory)
    {
        $this->typeFactory = $typeFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(RestPayload $payload)
    {
        var_dump(__METHOD__);
        die();
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(Struct\Resource $resource, AdapterInterface $adapter)
    {
        $primaryData = $resource->getPrimaryData();
        $serialized['data'] = $this->serializeData($primaryData, $adapter);
        // return $this->serializeIncluded($serialized);

        return (0 === $this->depth) ? $this->encode($serialized) : $serialized;
    }

    /**
     * Serializes a dataset into the appropriate format.
     *
     * @param   mixed               $data
     * @param   AdapterInterface    $adapter
     * @return  array
     * @throws  RuntimeException
     */
    protected function serializeData($data, AdapterInterface $adapter)
    {
        if ($data instanceof Struct\Entity) {
            $serialized = $this->serializeEntity($data, $adapter);
        } elseif ($data instanceof Struct\Identifier) {
            $serialized = $this->serializeIdentifier($data, $adapter);
        } elseif ($data instanceof Struct\Collection) {
            $serialized = $this->serializeCollection($data, $adapter);
        } elseif (null === $data) {
            $serialized = null;
        } else {
            throw new RuntimeException('Unable to serialize the provided data.');
        }
        return $serialized;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeCollection(Struct\Collection $collection, AdapterInterface $adapter)
    {
        $serialized = [];
        foreach ($collection as $entityInterface) {
            $serialized[] = $this->serializeData($entityInterface, $adapter);
        }
        return $serialized;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeIdentifier(Struct\Identifier $identifier, AdapterInterface $adapter)
    {
        $serialized = [
            'type'  => $adapter->getExternalEntityType($identifier->getType()),
            'id'    => $identifier->getId(),
        ];
        return $serialized;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeEntity(Struct\Entity $entity, AdapterInterface $adapter)
    {
        $metadata = $adapter->getEntityMetadata($entity->getType());

        $serialized = [
            'type'  => $adapter->getExternalEntityType($metadata->type),
            'id'    => $entity->getId(),
        ];
        if ($this->depth > 0) {
            // $this->includeResource($resource);
            return $serialized;
        }

        foreach ($metadata->getAttributes() as $key => $attrMeta) {
            $attribute = $entity->getAttribute($key);
            $formattedKey = $adapter->getExternalFieldKey($key);
            $serialized['attributes'][$formattedKey] = $this->serializeAttribute($attribute, $attrMeta);
        }

        $serialized['links'] = ['self' => $adapter->buildUrl($metadata, $entity->getId())];

        foreach ($metadata->getRelationships() as $key => $relMeta) {
            $relationship = $entity->getRelationship($key);
            $formattedKey = $adapter->getExternalFieldKey($key);
            $serialized['relationships'][$formattedKey] = $this->serializeRelationship($entity, $relationship, $relMeta, $adapter);
        }
        return $serialized;
    }

    /**
     * Serializes an attribute value.
     *
     * @param   Struct\Attribute|null   $attribute
     * @param   AttributeMetadata       $attrMeta
     * @return  mixed
     */
    protected function serializeAttribute(Struct\Attribute $attribute = null, AttributeMetadata $attrMeta)
    {
        if (null === $attribute) {
            return $this->typeFactory->convertToModlrValue($attrMeta->dataType, null);
        }
        // if ('object' === $attrMeta->dataType && $attrMeta->hasAttributes()) {
        //     // If object attributes (sub-attributes) are defined, attempt to convert them to the proper data types.
        //     $serialized = [];
        //     $values = get_object_vars($this->typeFactory->convertToPHPValue('object', $attribute->getValue()));
        //     foreach ($values as $key => $value) {
        //         if (null === $value) {
        //             continue;
        //         }
        //         if (false === $attrMeta->hasAttribute($key)) {
        //             continue;
        //         }
        //         $serialized[$attrMeta->externalKey] = $this->serializeAttribute(new Attribute($key, $value), $attrMeta->getAttribute($key));
        //     }
        //     return $serialized;
        // }
        return $this->typeFactory->convertToModlrValue($attrMeta->dataType, $attribute->getValue());
    }

    /**
     * Serializes a relationship value
     *
     * @todo    Need support for meta.
     *
     * @param   Struct\Entity               $owner
     * @param   Struct\Relationship|null    $relationship
     * @param   RelationshipMetadata        $relMeta
     * @param   AdapterInterface            $adapter
     * @return  array
     */
    protected function serializeRelationship(Struct\Entity $owner, Struct\Relationship $relationship = null, RelationshipMetadata $relMeta, AdapterInterface $adapter)
    {
        if (null === $relationship) {
            // No relationship data found, use default value.
            $relationship = new Struct\Relationship($relMeta->getKey(), $relMeta->getEntityType(), $relMeta->getRelType());
        }

        $this->increaseDepth();

        $serialized = $this->serialize($relationship, $adapter);

        $ownerMeta = $adapter->getEntityMetadata($owner->getType());
        $serialized['links'] = [
            'self'      => $adapter->buildUrl($ownerMeta, $owner->getId(), $relMeta->getKey()),
            'related'   => $adapter->buildUrl($ownerMeta, $owner->getId(), $relMeta->getKey(), true),
        ];
        $this->decreaseDepth();
        return $serialized;
    }

    /**
     * Encodes the formatted payload array.
     *
     * @param   array   $payload
     * @return  string
     */
    private function encode(array $payload)
    {
        return json_encode($payload);
    }

    /**
     * {@inheritDoc}
     */
    public function serializeError($message, $httpCode)
    {
        return $this->encode([
            'errors'    => [
                ['status' => (String) $httpCode, 'detail' => $message],
            ],
        ]);
    }

    /**
     * Increases the serializer depth.
     *
     * @return  self
     */
    protected function increaseDepth()
    {
        $this->depth++;
        return $this;
    }

    /**
     * Decreases the serializer depth.
     *
     * @return  self
     */
    protected function decreaseDepth()
    {
        if ($this->depth > 0) {
            $this->depth--;
        }
        return $this;
    }
}
