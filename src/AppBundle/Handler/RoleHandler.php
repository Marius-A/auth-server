<?php

namespace AppBundle\Handler;

use AppBundle\Entity\Role;
use AppBundle\Exception\ApiEntityNotFoundException;
use AppBundle\Repository\RoleRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use JMS\Serializer\Context;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class RoleHandler implements HandlerInterface
{
    const HANDLER_NAME = 'role.handler';

    /** @var SerializerInterface  */
    protected $serializer;

    /** @var Registry  */
    protected $doctrine;

    /**
     * @param SerializerInterface $serializer
     * @param Registry $doctrine
     */
    public function __construct(SerializerInterface $serializer, Registry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @throws ApiEntityNotFoundException
     * @return Role
     */
    public function get(array $parameters, Context $context)
    {
        /** @var Role $role */
        $role = $this->doctrine->getRepository('AppBundle:Role')->find($parameters['id']);

        if (empty($role)) {
            throw new ApiEntityNotFoundException(array('role.not_found' => 'Role not found'), Response::HTTP_NOT_FOUND);
        }

        return $role;
    }

    /**
     * @param $filters
     * @param $limit
     * @param $offset
     * @return array|mixed
     */
    public function all($filters, $limit, $offset)
    {
        /** @var RoleRepository $repository */
        $repository = $this->doctrine->getRepository('AppBundle:Role');
        return $repository->findBy(array('status' => Role::STATUS_ACTIVE), array(), $limit, $offset);
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     */
    public function post(array $parameters, Context $context)
    {
        // TODO: Implement post() method.
        return;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     */
    public function put(array $parameters, Context $context)
    {
        // TODO: Implement put() method.
        return;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed|void
     */
    public function delete(array $parameters, Context $context)
    {
        // TODO: Implement delete() method.
        return;
    }
}