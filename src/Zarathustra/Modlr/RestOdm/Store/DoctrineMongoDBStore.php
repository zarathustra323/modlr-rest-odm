<?php

namespace Zarathustra\Modlr\RestOdm\Store;

use Zarathustra\Modlr\RestOdm\Rest;
use Zarathustra\Modlr\RestOdm\Struct;
use Zarathustra\Modlr\RestOdm\Metadata\MetadataFactory;
use Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata;
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
        $result = $this->doctrineQueryRaw($className, ['id' => $identifier])->getSingleResult();
        if (null === $result) {
            throw StoreException::recordNotFound($metadata->type, $identifier);
        }
        return $this->hydrateOne($metadata, $identifier, $result);
    }

    /**
     * Hydrates a single set of MongoDB array data into a Struct\Resource object.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   array           $data
     * @return  Struct\Resource
     */
    protected function hydrateOne(EntityMetadata $metadata, $identifier, array $data)
    {
        $resource = $this->sf->createResource($metadata->type, 'one');
        $entity = $this->sf->createEntity($metadata->type, $identifier);

        $this->sf->applyEntity($resource, $entity);
        $this->sf->applyAttributes($entity, $data);

        $doctrineMeta = $this->getClassMetadata($metadata->type);

        foreach ($metadata->getRelationships() as $key => $relMeta) {
            if (false === $doctrineMeta->hasReference($key)) {
                continue;
            }
            if (!isset($data[$key]) || ($relMeta->isMany() && !is_array($data[$key]))) {
                continue;
            }

            $relEntityMeta = $this->mf->getMetadataForType($relMeta->getEntityType());
            $relDoctrineMeta = $this->getClassMetadata($relMeta->getEntityType());

            $fieldMapping = $doctrineMeta->getFieldMapping($key);
            $references = $relMeta->isOne() ? [$data[$key]] : $data[$key];

            $relationship = $this->sf->createRelationship($entity, $key);

            foreach ($references as $reference) {
                if (true === $fieldMapping['simple']) {
                    $referenceId = $reference;
                } elseif (is_array($reference) && isset($reference['$id'])) {
                    $referenceId = $reference['$id'];
                } else {
                    continue;
                }

                if (true === $relEntityMeta->isPolymorphic()) {
                    $discriminator = $relDoctrineMeta->discriminatorField;
                    $map = $relDoctrineMeta->discriminatorMap;

                    if (!isset($discriminator) || !is_array($reference) || !isset($reference[$discriminator]) || !isset($map[$reference[$discriminator]])) {
                        throw new RuntimeException('Unable to extract polymorphic type.');
                    }
                    $referenceType = $this->config->getTypeForClassName($map[$reference[$discriminator]]);
                } else {
                    $referenceType = $relEntityMeta->type;
                }
                $this->sf->applyRelationship($entity, $relationship, new Struct\Identifier($referenceId, $referenceType));
            }
        }
        return $resource;
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
    protected function doctrineQuery($className, array $criteria)
    {
        $qb = $this->dm->createQueryBuilder($className);

        $qb
            ->find()
            ->setQueryArray($criteria)
        ;
        return $qb->getQuery()->execute();
    }

    /**
     * Queries MongoDB via Doctrine's QueryBuilder, but returns a raw Cursor (no Doctrine hydration).
     *
     * @param   string  $className
     * @param   array   $criteria
     */
    protected function doctrineQueryRaw($className, array $criteria)
    {
        return $this->doctrineQuery($className, $criteria)->getBaseCursor();
    }
}
