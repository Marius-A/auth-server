<?php

namespace AppBundle\Handler;

use AppBundle\Entity\RoleTemplate;
use AppBundle\Entity\User;
use AppBundle\Exception\ApiEntityNotFoundException;
use AppBundle\Exception\ApiException;
use AppBundle\Exception\Exception;
use AppBundle\Service\UserService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use JMS\Serializer\Context;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRoleTemplateHandler implements HandlerInterface
{
    const HANDLER_NAME = 'user_role_template.handler';

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @param UserService $userService
     */
    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }

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
     * @return object
     * @throws ApiException
     * @throws Exception
     */
    public function put(array $parameters, Context $context)
    {
        $user = $this->doctrine->getRepository('AppBundle:User')->find($parameters['user_id']);
        if (!$user instanceof User) {
            throw new ApiEntityNotFoundException(array('user.not_found' => 'User not found'), Response::HTTP_NOT_FOUND);
        }
        
        $roleTemplate = $this->doctrine->getRepository('AppBundle:RoleTemplate')->find($parameters['role_template_id']);
        if (!$roleTemplate instanceof RoleTemplate) {
            throw new ApiEntityNotFoundException(array('role_template.not_found' => 'Role template not found'), Response::HTTP_NOT_FOUND);
        }
        
        $this->userService->applyRoleTemplateOnUser($user, $roleTemplate);

        try {
            $this->userService->save($user, $context);
        } catch (Exception $e) {
            throw new ApiException($e->getMessages(), $e->getCode());
        }

        return $user;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @throws ApiEntityNotFoundException
     * @return object
     */
    public function get(array $parameters, Context $context)
    {
        // TODO: Implement get() method.
    }

    /**
     * @param $filters
     * @param $limit
     * @param $offset
     * @return mixed
     */
    public function all($filters, $limit, $offset)
    {
        // TODO: Implement all() method.
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     */
    public function post(array $parameters, Context $context)
    {
        // TODO: Implement post() method.
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed|void
     */
    public function delete(array $parameters, Context $context)
    {
        // TODO: Implement delete() method.
    }
}
