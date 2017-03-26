<?php

namespace AppBundle\Service;

use AppBundle\Service\Interfaces\FilterInterface;
use AppBundle\Utils\StringUtil;
use Doctrine\ORM\Query\Expr as Expr;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use Metadata\MergeableClassMetadata;
use Metadata\MetadataFactory;

class FilterService implements FilterInterface
{
    const FILTER_MODIFIER_GREATER = '>';
    const FILTER_MODIFIER_LOWER = '<';
    const FILTER_MODIFIER_EQUAL = '=';

    const FILTER_MODIFIER_CONTAINS = '*';
    const FILTER_MODIFIER_STARTS_WITH = '^';
    const FILTER_MODIFIER_ENDS_WITH = '$';

    const ORDER_MODIFIER_DESC = '-';
    const ORDER_MODIFIER_ASC = '+';

    /**
     * @var MetadataFactory
     */
    protected $jmsMetadataFactory;

    /**
     * Store parameter count of every query builder passed to this service
     */
    protected $queryParameters = array();

    /**
     * @param MetadataFactory $jmsMetadataFactory
     */
    public function setJmsMetadataFactory($jmsMetadataFactory)
    {
        $this->jmsMetadataFactory = $jmsMetadataFactory;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $filters
     * @param $order
     * @param $limit
     * @param $offset
     * @return QueryBuilder
     */
    public function apply(QueryBuilder &$queryBuilder, $filters = array(), $order = null, $limit = 10, $offset = 0)
    {
        $this->applyFilters($queryBuilder, $filters);
        $this->applyOrder($queryBuilder, $order);

        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param $filters
     */
    protected function applyFilters(QueryBuilder &$queryBuilder, $filters)
    {
        if (empty($filters)) {
            return;
        }
        foreach ($filters as $filter => $value) {
            if (preg_match(':^(.*)([' . implode(self::getFilterModifiers()) . ']{1})$:Uis', $filter, $matches)) {
                $field = $matches[1];
                $modifier = $matches[2];
            } else {
                $field = $filter;
                $modifier = self::FILTER_MODIFIER_EQUAL;
            }
            $this->applyFilter($queryBuilder, $field, $modifier, $value);
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param $order
     */
    protected function applyOrder(QueryBuilder &$queryBuilder, $order)
    {
        if (empty($order)) {
            return;
        }
        $order = explode(',', $order);
        $aliases = $queryBuilder->getRootAliases();
        $alias = $aliases[0];
        foreach ($order as $orderBy) {
            if (preg_match(':^(.*)([' . implode(self::getOrderModifiers()) . ']{1})$:Uis', $orderBy, $matches)) {
                $field = $matches[1];
                $modifier = $matches[2];
            } else {
                $field = $orderBy;
                $modifier = self::ORDER_MODIFIER_ASC;
            }
            $direction = array_search($modifier, self::getOrderModifiers(), true);
            if ($direction) {
                $orderByExpression = new Expr\OrderBy($alias . '.' . $field, $direction);
                $queryBuilder->addOrderBy($orderByExpression);
            }
        }
    }

    /**
     * @return array
     */
    public static function getOrderModifiers()
    {
        return array(
            'DESC' => self::ORDER_MODIFIER_DESC,
            'ASC' => self::ORDER_MODIFIER_ASC
        );
    }

    /**
     * @return array
     */
    public static function getFilterModifiers()
    {
        return array(
            'endsWith' => self::FILTER_MODIFIER_ENDS_WITH,
            'equal' => self::FILTER_MODIFIER_EQUAL,
            'startsWith' => self::FILTER_MODIFIER_STARTS_WITH,
            'contains' => self::FILTER_MODIFIER_CONTAINS,
            'greater' => self::FILTER_MODIFIER_GREATER,
            'lower' => self::FILTER_MODIFIER_LOWER
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param $field
     * @param $modifier
     * @param $value
     * @return mixed|void
     * @throws \InvalidArgumentException
     */
    public function applyFilter(QueryBuilder &$queryBuilder, $field, $modifier, $value)
    {
        if (!isset($this->queryParameters[spl_object_hash($queryBuilder)])) {
            $this->queryParameters[spl_object_hash($queryBuilder)] = array();
        }

        $entities = $queryBuilder->getRootEntities();
        if (empty($entities)) {
            throw new \InvalidArgumentException('Query builder has no root entity');
        }
        $classMetadata = $this->jmsMetadataFactory->getMetadataForClass($entities[0]);

        $property = null;
        $method = null;
        try {
            $method = "apply".ucfirst(array_search($modifier, $this->getFilterModifiers()));
            $property = $this->getProperty($classMetadata, $field);
        } catch (\LogicException $e) {
            $method = "applyCustom".ucfirst(array_search($modifier, $this->getFilterModifiers())).ucfirst(StringUtil::toCamelCase($field));
        }

        if(!is_callable(array($this, $method))) {
            throw new \LogicException('Modifier does not exist: ' . $modifier);
        }
        $this->$method($queryBuilder, $value, $property);
    }

    /**
     * @param MergeableClassMetadata $classMetadata
     * @param $field
     * @return int|string
     * @throws \LogicException
     */
    protected function getProperty(MergeableClassMetadata $classMetadata, $field)
    {
        foreach ($classMetadata->propertyMetadata as $property => $propertyMetadata)
        {
            /** @var PropertyMetadata $propertyMetadata */
            if ($propertyMetadata->serializedName == $field || $field == $property) {
                return $property;
            }
        }
        throw new \LogicException('Filter does not match a jms serializer name');
    }

    protected function getParameterQueryBuilder(QueryBuilder &$queryBuilder, $property)
    {
        if (!isset($this->queryParameters[spl_object_hash($queryBuilder)][$property])) {
            $this->queryParameters[spl_object_hash($queryBuilder)][$property] = 0;
        }

        $parameter = $property . '_' . ($this->queryParameters[spl_object_hash($queryBuilder)][$property]++);

        return $parameter;
    }

    protected function applyEqual(QueryBuilder &$queryBuilder, $value, $property)
    {
        $alias = $this->getAliasQueryBuilder($queryBuilder);

        $queryParts = array();
        if (is_array($value)) {
            foreach ($value as $v) {
                $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
                $queryParts[] = $queryBuilder->expr()->eq($alias . '.' . $property, ':' . $parameter);
                $queryBuilder->setParameter($parameter, $v);
            }

        } else {
            $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
            $queryParts[] = $queryBuilder->expr()->eq($alias . '.' . $property, ':' . $parameter);
            $queryBuilder->setParameter($parameter, $value);
        }
        $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple($queryParts));
    }

    protected function applyStartsWith(QueryBuilder &$queryBuilder, $value, $property)
    {
        $alias = $this->getAliasQueryBuilder($queryBuilder);

        $queryParts = array();
        if (is_array($value)) {
            foreach ($value as $v) {
                $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
                $queryParts[] = $queryBuilder->expr()->like($alias . '.' . $property, ':' . $parameter);
                $queryBuilder->setParameter($parameter, $v . '%');
            }
        } else {
            $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
            $queryParts[] = $queryBuilder->expr()->like($alias . '.' . $property, ':' . $parameter);
            $queryBuilder->setParameter($parameter, $value . '%');
        }
        $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple($queryParts));
    }

    protected function applyEndsWith(QueryBuilder &$queryBuilder, $value, $property)
    {
        $alias = $this->getAliasQueryBuilder($queryBuilder);

        $queryParts = array();
        if (is_array($value)) {
            foreach ($value as $v) {
                $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
                $queryParts[] = $queryBuilder->expr()->like($alias . '.' . $property, ":" . $parameter);
                $queryBuilder->setParameter($parameter, '%' . $v);
            }
        } else {
            $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
            $queryParts[] = $queryBuilder->expr()->like($alias . '.' . $property, ':' . $parameter);
            $queryBuilder->setParameter($parameter, '%' . $value);
        }
        $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple($queryParts));
    }

    protected function applyContains(QueryBuilder &$queryBuilder, $value, $property)
    {
        $alias = $this->getAliasQueryBuilder($queryBuilder);

        $queryParts = array();
        if (is_array($value)) {
            foreach ($value as $v) {
                $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
                $queryParts[] = $queryBuilder->expr()->like($alias . '.' . $property, ':' . $parameter);
                $queryBuilder->setParameter($parameter, '%' . $v . '%');
            }
        } else {
            $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
            $queryParts[] = $queryBuilder->expr()->like($alias . '.' . $property, ':' . $parameter);
            $queryBuilder->setParameter($parameter, '%' . $value . '%');
        }
        $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple($queryParts));
    }

    protected function applyGreater(QueryBuilder &$queryBuilder, $value, $property)
    {
        $alias = $this->getAliasQueryBuilder($queryBuilder);

        $queryParts = array();
        if (is_array($value)) {
            foreach ($value as $v) {
                $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
                $queryParts[] = $queryBuilder->expr()->gt($alias . '.' . $property, ':' . $parameter);
                $queryBuilder->setParameter($parameter, $v);
            }
        } else {
            $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
            $queryParts[] = $queryBuilder->expr()->gt($alias . '.' . $property, ':' . $parameter);
            $queryBuilder->setParameter($parameter, $value);
        }
        $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple($queryParts));
    }

    protected function applyLower(QueryBuilder &$queryBuilder, $value, $property)
    {
        $alias = $this->getAliasQueryBuilder($queryBuilder);

        $queryParts = array();
        if(is_array($value)) {
            foreach ($value as $v) {
                $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
                $queryParts[] = $queryBuilder->expr()->lt($alias . '.' . $property, ':' . $parameter);
                $queryBuilder->setParameter($parameter, $v);
            }
        } else {
            $parameter = $this->getParameterQueryBuilder($queryBuilder, $property);
            $queryParts[] = $queryBuilder->expr()->lt($alias . '.' . $property, ':' . $parameter);
            $queryBuilder->setParameter($parameter, $value);
        }
        $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple($queryParts));
    }

    protected function getAliasQueryBuilder(QueryBuilder &$queryBuilder)
    {
        $aliases = $queryBuilder->getRootAliases();

        return $aliases[0];
    }
}
