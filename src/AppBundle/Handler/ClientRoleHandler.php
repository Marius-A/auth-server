<?php

namespace AppBundle\Handler;


use AppBundle\Exception\ApiException;
use AppBundle\Exception\Exception;
use AppBundle\Repository\RoleRepository;
use AppBundle\Server\OAuth2;
use AppBundle\Service\ClientService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Context;

class ClientRoleHandler implements HandlerInterface
{
    const HANDLER_NAME = 'client_role.handler';

    /** @var  Registry */
    protected $doctrine;

    /** @var  ClientHandler */
    protected $clientHandler;

    /** @var  RoleHandler */
    protected $roleHandler;

    /** @var  ClientService */
    protected $clientService;

    /** @var  OAuth2 */
    protected $server;

    /**
     * @param $clientId
     * @param $limit
     * @param $offset
     * @return array
     */
    public function clientAll($clientId, $limit, $offset)
    {
        /** @var RoleRepository $repository */
        $repository = $this->doctrine->getManager()->getRepository('AppBundle:Role');
        /** @var QueryBuilder $qb */
        $qb = $repository->createQueryBuilder('r');

        $qb->select('r');
        $qb->innerJoin('r.clients', 'c');
        $qb->where('c.id = :clientId');

        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        $qb->setParameter('clientId', $clientId);

        $query = $qb->getQuery();
        $roles = $query->getResult();

        return $roles;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     * @throws ApiException
     */
    public function put(array $parameters, Context $context)
    {
        $client = $this->clientHandler->get(array('id' => $parameters['client_id']), $context);
        $role = $this->roleHandler->get(array('id' => $parameters['role_id']), $context);

        $this->server->deleteClientTokenCacheIndex($client);
        $this->clientService->applyRoleOnClient($client, $role);

        try {
            $this->clientService->save($client, $context);
        } catch (Exception $e) {
            throw new ApiException($e->getMessages(), $e->getCode());
        }

        return $client;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     * @throws ApiException
     */
    public function delete(array $parameters, Context $context)
    {
        $client = $this->clientHandler->get(array('id' => $parameters['client_id']), $context);
        $role = $this->roleHandler->get(array('id' => $parameters['role_id']), $context);

        $this->server->deleteClientTokenCacheIndex($client);
        $this->clientService->deleteRoleOnClient($client, $role);

        try {
            $this->clientService->save($client, $context);
        } catch (Exception $e) {
            throw new ApiException($e->getMessages(), $e->getCode());
        }

        return $client;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed|void
     */
    public function get(array $parameters, Context $context)
    {
        // TODO: Implement get() method.
        return;
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
        return;
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
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param ClientHandler $clientHandler
     */
    public function setClientHandler($clientHandler)
    {
        $this->clientHandler = $clientHandler;
    }

    /**
     * @param RoleHandler $roleHandler
     */
    public function setRoleHandler($roleHandler)
    {
        $this->roleHandler = $roleHandler;
    }

    /**
     * @param ClientService $clientService
     */
    public function setClientService($clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @param OAuth2 $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

}