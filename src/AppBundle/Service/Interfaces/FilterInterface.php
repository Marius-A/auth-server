<?php

namespace AppBundle\Service\Interfaces;

use Doctrine\ORM\QueryBuilder;

interface FilterInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param array $filters
     * @param $order
     * @param $limit
     * @param $offset
     * @return QueryBuilder
     */
    public function apply(QueryBuilder &$queryBuilder, $filters = array(), $order = null, $limit = 10, $offset = 0);

    /**
     * @param QueryBuilder $queryBuilder
     * @param $field
     * @param $modifier
     * @param $value
     * @return mixed
     */
    public function applyFilter(QueryBuilder &$queryBuilder, $field, $modifier, $value);
}
