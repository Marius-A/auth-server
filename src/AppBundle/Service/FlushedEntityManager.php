<?php

namespace AppBundle\Service;

/**
 * This service class stores entity updates that where performed by doctrine unit of work.
 * This is useful when performing
 */
class FlushedEntityManager
{
    /**
     * A list of all flushed entity updates.
     *
     * @var array
     */
    protected $entityUpdates;

    public function __construct()
    {
        $this->resetEntityUpdates();
    }

    public function resetEntityUpdates()
    {
        $this->entityUpdates = array();
    }

    public function markAsUpdated($entity)
    {
        $this->entityUpdates[spl_object_hash($entity)] = $entity;
    }

    public function getEntityUpdates()
    {
        return $this->entityUpdates;
    }
}
