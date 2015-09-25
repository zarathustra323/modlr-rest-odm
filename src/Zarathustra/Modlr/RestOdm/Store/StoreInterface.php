<?php

namespace Zarathustra\Modlr\RestOdm\Store;

use Zarathustra\Modlr\RestOdm\Rest;
use Zarathustra\Modlr\RestOdm\Struct;
use Zarathustra\Modlr\RestOdm\Metadata\EntityMetadata;

/**
 * Interface for handling database operations
 *
 * @author Jacob Bare <jbare@southcomm.com>
 */
interface StoreInterface
{
    /**
     * Finds a single entity by id.
     *
     * @param   EntityMetadata  $type
     * @param   string          $identifier
     * @param   array           $fields
     * @param   array           $inclusions
     * @return  Struct\Resource
     */
    public function findRecord(EntityMetadata $metadata, $identifier, array $fields = [], array $inclusions = []);

    /**
     * Finds multiple entities by type.
     *
     * @param   EntityMetadata  $type
     * @param   array           $identifiers
     * @param   array           $pagination
     * @param   array           $fields
     * @param   array           $inclusions
     * @param   array           $sorts
     * @return  Struct\Resource
     */
    public function findMany(EntityMetadata $metadata, array $identifiers = [], array $pagination = [], array $fields = [], array $inclusions = [], array $sort = []);
}
