<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Role;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

class RoleRepository extends NestedTreeRepository
{
    /**
     * {@inheritDoc}
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $result = array();

        $roles = parent::children($node, $direct, $sortByField, $direction, $includeNode);
        if (empty($roles)) {
            return $result;
        }

        foreach($roles as $role) {
            /** @var Role $role */
            $result[$role->getId()] = $role;
        }
        return $result;
    }
} 