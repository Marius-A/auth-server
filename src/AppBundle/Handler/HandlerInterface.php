<?php

namespace AppBundle\Handler;

use AppBundle\Exception\ApiEntityNotFoundException;
use JMS\Serializer\Context;

interface HandlerInterface
{
    /**
     * @param array $parameters
     * @param Context $context
     * @throws ApiEntityNotFoundException
     * @return mixed
     */
    public function get(array $parameters, Context $context);

    /**
     * @param $filters
     * @param $limit
     * @param $offset
     * @return mixed
     */
    public function all($filters, $limit, $offset);

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     */
    public function post(array $parameters, Context $context);

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     */
    public function put(array $parameters, Context $context);

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     */
    public function delete(array $parameters, Context $context);
}
