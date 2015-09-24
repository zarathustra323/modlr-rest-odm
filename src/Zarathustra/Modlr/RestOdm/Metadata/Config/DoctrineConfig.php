<?php

namespace Zarathustra\Modlr\RestOdm\Metadata\Config;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Zarathustra\Modlr\RestOdm\Exception\MetadataException;

/**
 * Doctrine Metadata config helper class.
 *
 * @author Jacob Bare <jacob.bare@southcomm.com>
 */
class DoctrineConfig
{
     /**
     * Root namespace that all Doctrine class names share.
     * Is used to convert class names to entity types, and vice versa.
     *
     * @param string|null
     */
    protected $rootNamespace;

    /**
     * Constructor.
     *
     * @param   string|null     $rootNamespace
     */
    public function __construct($rootNamespace = null)
    {
        $this->rootNamespace = $rootNamespace;
    }

    /**
     * Gets the entity type from a Doctrine class name.
     *
     * @param   string  $className
     * @return  string
     */
    public function getTypeForClassName($className)
    {
        if (empty($this->rootNamespace)) {
            return $className;
        }
        return $this->stripNamespace($this->rootNamespace, $className);
    }

    protected function stripNamespace($namespace, $toStrip)
    {
        return trim(str_replace($namespace, '', $toStrip), '\\');
    }

    /**
     * Gets the Doctrine class name from an entity type.
     *
     * @param   string  $type
     * @return  string
     */
    public function getClassNameForType($type)
    {
        if (!empty($this->rootNamespace) && strstr($type, $this->rootNamespace)) {
            $type = $this->stripNamespace($this->rootNamespace, $type);
        }
        if (!empty($this->rootNamespace)) {
            return sprintf('%s\\%s', $this->rootNamespace, $type);
        }
        return $type;
    }
}
