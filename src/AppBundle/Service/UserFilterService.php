<?php

namespace AppBundle\Service;


use Doctrine\ORM\QueryBuilder;

class UserFilterService extends FilterService
{
    protected function applyCustomEqualRoleId(QueryBuilder &$queryBuilder, $value, $property)
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        if (!$queryBuilder->getDQLPart('where')) {
            $queryBuilder->where("1 = 1");
        }
        $queryBuilder->innerJoin('u.roles', 'r');
        $queryBuilder->andWhere($queryBuilder->expr()->in('r.id',$value));
    }
}