<?php

namespace Zarathustra\Modlr\RestOdm\Metadata\Driver;

use Zarathustra\Modlr\RestOdm\Metadata\Config\DoctrineConfig;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Zarathustra\Modlr\RestOdm\Exception\MetadataException;

/**
 * The abstract Doctrine metadata driver.
 *
 * @author Jacob Bare <jacob.bare@southcomm.com>
 */
abstract class AbstractDoctrineDriver implements DriverInterface
{
    /**
     * A Doctrine class metadata factory implementation.
     *
     * @var ClassMetadataFactory
     */
    protected $mf;

    /**
     * Array cache of all entity types.
     *
     * @var null|array
     */
    protected $allEntityTypes;

    /**
     * Doctrine metadata configuration.
     *
     * @var DoctrineConfig
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param   ClassMetadataFactory    $mf
     * @param   DoctrineConfig          $config
     */
    public function __construct(ClassMetadataFactory $mf, DoctrineConfig $config)
    {
        $this->mf = $mf;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForType($type)
    {
        return $this->loadFromClassMetadata($this->doLoadClassMetadata($type));
    }

    /**
     * Loads Doctrine ClassMetadata for an entity type.
     *
     * @param   string  $type
     * @return  ClassMetadata|null
     */
    protected function doLoadClassMetadata($type)
    {
        if (false === $this->classMetadataExists($type)) {
            return null;
        }

        $className = $this->getClassNameForType($type);
        return $this->mf->getMetadataFor($className);
    }

    /**
     * Determines if Doctrine ClassMetadata exists for an entity type.
     *
     * @param   string  $type
     * @return  bool
     */
    protected function classMetadataExists($type)
    {
        $className = $this->getClassNameForType($type);
        try {
            $metadata = $this->mf->getMetadataFor($className);
            return true;
        } catch (MappingException $e) {
            return false;
        }
        return false === $this->shouldFilterClassMetadata($metadata);
    }

    /**
     * Loads an entity metadata object from Doctrine ClassMetadata.
     *
     * @abstract
     * @param   ClassMetadata   $metadata
     * @return  \Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata
     */
    abstract protected function loadFromClassMetadata(ClassMetadata $metadata);

    /**
     * {@inheritDoc}
     */
    public function getAllTypeNames()
    {
        if (null === $this->allEntityTypes) {
            $this->allEntityTypes = [];
            foreach ($this->mf->getAllMetadata() as $metadata) {
                if (true === $this->shouldFilterClassMetadata($metadata)) {
                    // Do not include filtered metadata.
                    continue;
                }
                $this->allEntityTypes[] = $this->getTypeForClassName($metadata->getName());
            }
        }
        return $this->allEntityTypes;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getTypeHierarchy($type, array $types = []);

    /**
     * Determines if a Doctrine ClassMetadata instance should be filtered (not included).
     * Must be extended to take effect, per Doctrine driver type.
     *
     * @param   ClassMetadata   $metadata
     * @return  bool
     */
    protected function shouldFilterClassMetadata(ClassMetadata $metadata)
    {
        return false;
    }

    /**
     * Gets the entity type from a Doctrine class name.
     *
     * @param   string  $className
     * @return  string
     */
    protected function getTypeForClassName($className)
    {
        return $this->config->getTypeForClassName($className);
    }

    /**
     * Gets the Doctrine class name from an entity type.
     *
     * @param   string  $type
     * @return  string
     */
    protected function getClassNameForType($type)
    {
        return $this->config->getClassNameForType($type);
    }
}
