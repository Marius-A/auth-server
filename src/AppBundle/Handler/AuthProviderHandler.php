<?php

namespace AppBundle\Handler;

use AppBundle\Entity\AuthProvider;
use AppBundle\Exception\ApiEntityNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Registry;
use JMS\Serializer\Context;
use Symfony\Component\HttpFoundation\Response;

class AuthProviderHandler implements HandlerInterface
{
    const HANDLER_NAME = 'auth_provider.handler';

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return AuthProvider
     * @throws ApiEntityNotFoundException
     */
    public function get(array $parameters, Context $context)
    {
        /** @var AuthProvider $authProvider */
        $authProvider = $this->doctrine
            ->getRepository('AppBundle:AuthProvider')
            ->find(
                $parameters['id']
            );

        if (empty($authProvider)) {
            throw new ApiEntityNotFoundException(
                array(
                    'auth_provider.not_found' => 'Auth provider not found'
                ), Response::HTTP_NOT_FOUND);
        }

        return $authProvider;
    }

    public function all($filters, $limit, $offset)
    {
        // TODO: Implement all() method.
    }

    public function post(array $parameters, Context $context)
    {
        // TODO: Implement post() method.
    }

    public function put(array $parameters, Context $context)
    {
        // TODO: Implement put() method.
    }

    public function delete(array $parameters, Context $context)
    {
        // TODO: Implement delete() method.
    }
}