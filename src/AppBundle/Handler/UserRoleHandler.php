<?php

namespace AppBundle\Handler;


use AppBundle\Entity\Client;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Exception\ApiEntityNotFoundException;
use AppBundle\Exception\ApiException;
use AppBundle\Exception\Exception;
use AppBundle\Repository\RoleRepository;
use AppBundle\Server\OAuth2;
use AppBundle\Service\ClientService;
use AppBundle\Service\UserService;
use AppBundle\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Context;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRoleHandler implements HandlerInterface
{
    const HANDLER_NAME = 'user_role.handler';

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var OAuth2
     */
    protected $server;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var ClientService
     */
    protected $clientService;

    /**
     * @var UserHandler
     */
    protected $userHandler;

    /**
     * @var RoleHandler
     */
    protected $roleHandler;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param OAuth2 $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @param UserService $userService
     */
    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param ClientService $clientService
     */
    public function setClientService($clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @param UserHandler $userHandler
     */
    public function setUserHandler($userHandler)
    {
        $this->userHandler = $userHandler;
    }

    /**
     * @param RoleHandler $roleHandler
     */
    public function setRoleHandler($roleHandler)
    {
        $this->roleHandler = $roleHandler;
    }

    /**
     * @param $userId
     * @param Client $client
     * @param bool $recursive
     * @return array
     */
    public function userAll($userId, Client $client = null, $recursive = true)
    {
        $userRepository = $this->doctrine->getManager()->getRepository('AppBundle:User');
        $roleRepository = $this->doctrine->getManager()->getRepository('AppBundle:Role');

        /** @var User $user */
        $user = $userRepository->find($userId);

        if (!($user instanceof User)) {
            return array();
        }

        if (!is_null($client)) {
            $clientRoles =  $this->clientService->getClientRoles($client, true);
        }

        $unindexedRoles = array();
        $roles = $user->getRoles();
        foreach ($roles as $role) {
            /** @var Role $role */
            if (!$client || ($client && isset($clientRoles[$role->getId()]))) {
                $unindexedRoles[] = $role;
                if ($recursive) {
                    $unindexedRoles = array_merge($unindexedRoles, $roleRepository->children($role)); //array_merge resets indexes
                }
            }
        }
        
        $result = array();
        foreach ($unindexedRoles as $role) {
            $result[$role->getId()] = $role;
        }

        return $result;
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
        $user = $this->userHandler->get(array('id' => $parameters['user_id']), $context);
        $role = $this->roleHandler->get(array('id' => $parameters['role_id']), $context);

        $this->server->deleteUserTokenCacheIndex($user);
        $this->userService->applyRoleOnUser($user, $role);

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
     * @return User
     * @throws ApiEntityNotFoundException
     * @throws ApiException
     * @throws Exception
     */
    public function delete(array $parameters, Context $context) {
        $user = $this->userHandler->get(array('id' => $parameters['user_id']), $context);
        $role = $this->roleHandler->get(array('id' => $parameters['role_id']), $context);

        $this->server->deleteUserTokenCacheIndex($user);
        $this->userService->deleteRoleOnUser($user, $role);

        try {
            $this->userService->save($user);
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
}
