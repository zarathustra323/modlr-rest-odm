<?php

namespace Zarathustra\Modlr\RestOdm\Store;

use Zarathustra\Modlr\RestOdm\Rest;
use Zarathustra\Modlr\RestOdm\Struct;
use Zarathustra\Modlr\RestOdm\Metadata\MetadataFactory;
use Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata;
use Zarathustra\Modlr\RestOdm\Metadata\RelationshipMetadata;
use Zarathustra\Modlr\RestOdm\Metadata\Config\DoctrineConfig;
use Doctrine\ODM\MongoDB\DocumentManager;
use Zarathustra\Modlr\RestOdm\Exception\RuntimeException;

/**
 * Store for Doctrine MongoDB database operations.
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
class DoctrineMongoDBStore implements StoreInterface
{
    /**
     * Modlr MetadataFactory
     *
     * @var MetadataFactory.
     */
    private $mf;

    /**
     * The Doctrine DocumentManager.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The Doctrine-to-Modlr Metadata configuration.
     *
     * @var DoctrineConfig
     */
    private $config;

    /**
     * The resource structure factory.
     *
     * @var Struct\StructFactory
     */
    private $sf;

    /**
     * Entities and identifiers marked for inclusion.
     *
     * @var array
     */
    private $included = [];

    /**
     * Constructor.
     *
     * @param   MetadataFactory         $mf
     * @param   DocumentManager         $dm
     * @param   DoctrineConfig          $config
     * @param   Struct\StructFactory    $sf
     */
    public function __construct(MetadataFactory $mf, DocumentManager $dm, DoctrineConfig $config, Struct\StructFactory $sf)
    {
        $this->mf = $mf;
        $this->dm = $dm;
        $this->config = $config;
        $this->sf = $sf;
    }

    /**
     * {@inheritDoc}
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = [])
    {
        $className = $this->config->getClassNameForType($metadata->type);
        $result = $this->doctrineQueryRaw($className, ['id' => $identifier], $fields)->getSingleResult();
        if (null === $result) {
            throw StoreException::recordNotFound($metadata->type, $identifier);
        }
        return $this->hydrateOne($metadata, $identifier, $result, $inclusions);
    }

    /**
     * {@inheritDoc}
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = [])
    {
        $className = $this->config->getClassNameForType($metadata->type);

        $criteria = [];
        if (!empty($identifiers)) {
            $criteria['id'] = ['$in' => $identifiers];
        }
        $cursor = $this->doctrineQueryRaw($className, [], $fields, $sort)->limit($pagination['limit'])->skip($pagination['offset']);
        return $this->hydrateMany($metadata, $cursor->toArray(), $inclusions);
    }

    /**
     * Hydrates a single MongoDB array record into a Struct\Resource object.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $data
     * @param   array           $inclusions
     * @return  Struct\Resource
     */
    protected function hydrateOne(EntityMetadata $metadata, $identifier, array $data, array $inclusions)
    {
        $resource = $this->sf->createResource($metadata->type, 'one');
        $entity = $this->hydrateEntity($metadata, $identifier, $data, $inclusions);
        $this->sf->applyEntity($resource, $entity);
        $resource->setIncludedData($this->hydrateIncluded());
        return $resource;
    }

    /**
     * Hydrates multiple MongoDB array records into a Struct\Resource object.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $items
     * @param   array           $data
     * @param   array           $inclusions
     * @return  Struct\Resource
     */
    protected function hydrateMany(EntityMetadata $metadata, array $items, array $inclusions)
    {
        $resource = $this->sf->createResource($metadata->type, 'many');
        foreach ($items as $identifier => $data) {
            $entity = $this->hydrateEntity($metadata, $identifier, $data, $inclusions);
            $this->sf->applyEntity($resource, $entity);
        }
        $resource->setIncludedData($this->hydrateIncluded());
        return $resource;
    }

    /**
     * Hydrates included (side-loaded) data in a Struct\Collection of Struct\Entity objects.
     *
     * @return  Struct\Collection
     */
    protected function hydrateIncluded()
    {
        $collection = $this->sf->createCollection();
        foreach ($this->included as $type => $identifiers) {
            $metadata = $this->mf->getMetadataForType($type);
            $className = $this->config->getClassNameForType($metadata->type);

            $cursor = $this->doctrineQueryRaw($className, ['id' => ['$in' => array_keys($identifiers)]]);
            foreach ($cursor as $data) {
                $identifier = $data['_id'];
                $entity = $this->hydrateEntity($metadata, $identifier, $data, []);
                $collection->add($entity);
            }
        }
        $this->included = [];
        return $collection;
    }

    /**
     * Hydrates a single MongoDB record into a Struct\Entity object.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $data
     * @param   array           $inclusions
     * @return  Struct\Entity
     */
    protected function hydrateEntity(EntityMetadata $metadata, $identifier, array $data, array $inclusions)
    {
        $metadata = $this->extractPolymorphicMetadata($metadata, $data);

        // @todo This shouldn't run here: findMany will hit this method 50 times!
        $inclusions = $this->getInclusions($metadata, $inclusions);

        $entity = $this->sf->createEntity($metadata->type, $identifier);
        $this->sf->applyAttributes($entity, $data);

        $doctrineMeta = $this->getClassMetadata($metadata->type);

        foreach ($metadata->getRelationships() as $key => $relMeta) {
            // @todo THIS MUST USE MODLR METADATA, AS MUTATION FIELDS DON'T EXIST ON DOCTRINE METADATA!!!
            if (false === $doctrineMeta->hasReference($key)) {
                continue;
            }
            if (!isset($data[$key]) || ($relMeta->isMany() && !is_array($data[$key]))) {
                continue;
            }

            $fieldMapping = $doctrineMeta->getFieldMapping($key);
            $references = $relMeta->isOne() ? [$data[$key]] : $data[$key];

            $relationship = $this->sf->createRelationship($entity, $key);
            foreach ($references as $reference) {
                list($referenceId, $referenceType) = $this->extractReference($relMeta, $reference, $fieldMapping['simple']);

                if (false === $relMeta->isInverse && isset($inclusions[$key])) {
                    // @todo MUST HANDLE INVERSE INCLUSIONS
                    $this->markForInclusion($referenceType, $referenceId);
                }

                $this->sf->applyRelationship($entity, $relationship, new Struct\Identifier($referenceId, $referenceType));
            }
        }
        return $entity;
    }

    /**
     * Marks an entity type and identifier for inclusion.
     *
     * @param   string  $type
     * @param   mixed   $identifier
     */
    protected function markForInclusion($type, $identifier)
    {
        $this->included[$type][(String) $identifier] = true;
        return $this;
    }

    /**
     * Gets the fields to include, based on defaults, and validates relationship keys.
     *
     * @param   EntityMetadata  $metadata
     * @param   array           $inclusions
     * @return  array
     * @throws  StoreException
     */
    protected function getInclusions(EntityMetadata $metadata, array $inclusions)
    {
        if (empty($inclusions)) {
            // No inclusions.
            return $inclusions;
        }
        if (isset($inclusions['*'])) {
            // Include all.
            $formatted = [];
            foreach (array_keys($metadata->getRelationships()) as $fieldKey) {
                $formatted[$fieldKey] = true;
            }
            return $formatted;
        }
        // Specified.
        foreach ($inclusions as $fieldKey => $inclusion) {
            if (false === $metadata->hasRelationship($fieldKey)) {
                throw StoreException::invalidInclude($metadata->type, $fieldKey);
            }
        }
        return $inclusions;
    }

    /**
     * Extracts an entity type and identifier from a Doctrine MongoDB reference.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   mixed                   $reference
     * @param   bool                    $simple
     * @return  array
     * @throws  RuntimeException
     */
    protected function extractReference(RelationshipMetadata $relMeta, $reference, $simple)
    {
        $simple = (Boolean) $simple;
        $relEntityMeta = $this->mf->getMetadataForType($relMeta->getEntityType());
        $doctrineMeta = $this->getClassMetadata($relEntityMeta->type);

        if (true === $simple && is_array($reference) && isset($reference['$id'])) {
            $referenceId = $reference['$id'];
        } elseif (true === $simple && !is_array($reference)) {
            $referenceId = $reference;
        } elseif (false === $simple && is_array($reference) && isset($reference['$id'])) {
            $referenceId = $reference['$id'];
        } else {
            throw new RuntimeException('Unable to extract a reference id.');
        }

        $extracted = $this->extractPolymorphicMetadata($relEntityMeta, $reference);
        return [$referenceId, $extracted->type];
    }

    /**
     * Extracts the proper, polymorphic metadata, based on the incoming MongoDB data.
     * If the entity is not polymorphic, the passed metadata is returned.
     *
     * @param   EntityMetadata  $metadata
     * @param   mixed           $data
     * @return  EntityMetadata
     * @throws  RuntimeException
     */
    protected function extractPolymorphicMetadata(EntityMetadata $metadata, $data)
    {
        if (false === $metadata->isPolymorphic()) {
            return $metadata;
        }

        if (!is_array($data)) {
            throw new RuntimeException('Unable to extract polymorphic type');
        }

        $discrim = $this->getDoctrineDiscriminator($metadata->type);
        if (!isset($discrim['field']) || !isset($data[$discrim['field']]) || !isset($discrim['map'][$data[$discrim['field']]])) {
            throw new RuntimeException('Unable to extract polymorphic type.');
        }
        $type = $this->config->getTypeForClassName($discrim['map'][$data[$discrim['field']]]);
        return $this->mf->getMetadataForType($type);
    }

    /**
     * Gets the Doctrine discriminator field and map for an entity, as an associative array.
     *
     * @param   $entityType
     * @return  array
     */
    protected function getDoctrineDiscriminator($entityType)
    {
        $doctrineMeta = $this->getClassMetadata($entityType);
        return [
            'field' => $doctrineMeta->discriminatorField,
            'map'   => $doctrineMeta->discriminatorMap,
        ];
    }

    /**
     * Gets the Doctrine ClassMetadata object from an entity type.
     *
     * @param   string  $entityType
     * @return  ClassMetadata
     */
    protected function getClassMetadata($entityType)
    {
        $className = $this->config->getClassNameForType($entityType);
        return $this->dm->getClassMetadata($className);
    }

    /**
     * Queries MongoDB via Doctrine's QueryBuilder
     *
     * @param   string  $className
     * @param   array   $criteria
     */
    protected function doctrineQuery($className, array $criteria, array $fields = [], array $sort = [])
    {
        $qb = $this->dm->createQueryBuilder($className)
            ->find()
            ->setQueryArray($criteria)
        ;

        $qb->select($fields);

        if (!empty($sort)) {
            $qb->sort($sort);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Queries MongoDB via Doctrine's QueryBuilder, but returns a raw Cursor (no Doctrine hydration).
     *
     * @param   string  $className
     * @param   array   $criteria
     */
    protected function doctrineQueryRaw($className, array $criteria, array $fields = [], array $sort = [])
    {
        return $this->doctrineQuery($className, $criteria, $fields, $sort)->getBaseCursor();
    }
}
