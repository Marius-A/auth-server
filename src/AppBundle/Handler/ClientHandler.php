<?php

namespace AppBundle\Handler;

use AppBundle\Entity\Client;
use AppBundle\Exception\ApiEntityNotFoundException;
use AppBundle\Exception\ApiException;
use AppBundle\Exception\ApiValidationException;
use AppBundle\Exception\Exception;
use AppBundle\Service\ClientService;
use AppBundle\Service\Interfaces\FilterInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator;

class ClientHandler implements HandlerInterface
{
    const HANDLER_NAME = 'client.handler';

    /** @var SerializerInterface  */
    private $serializer;
    /** @var Registry  */
    private $doctrine;
    /** @var  Validator\ */
    private $validator;
    /** @var  FilterInterface */
    private $filterService;
    /** @var  ClientService */
    private $clientService;

    /**
     * @param Validator\ $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param mixed $filterService
     */
    public function setFilterService(FilterInterface $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * @param ClientService $clientService
     */
    public function setClientService($clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @param SerializerInterface $serializer
     * @param Registry $doctrine
     */
    public function __construct(SerializerInterface $serializer, Registry $doctrine)
    {
        $this->serializer = $serializer;
        $this->doctrine = $doctrine;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @throws ApiEntityNotFoundException
     * @return Client
     */
    public function get(array $parameters, Context $context)
    {
        /** @var Client $client */
        $client = $this->doctrine
            ->getRepository('AppBundle:Client')
            ->find(
                $parameters['id']
            );

        if (empty($client)) {
            throw new ApiEntityNotFoundException(array('client.not_found' => 'Client not found'), Response::HTTP_NOT_FOUND);
        }

        return $client;
    }

    /**
     * @param $filters
     * @param $order
     * @param int $limit
     * @param int $offset
     * @return \AppBundle\Entity\Client[]|array|mixed
     * @throws \AppBundle\Exception\ApiException
     */
    public function all($filters=array(), $order = null, $limit = 10, $offset = 0)
    {
        $queryBuilder = $this->doctrine->getRepository('AppBundle:Client')->createQueryBuilder('c');
        return $this->filterService->apply($queryBuilder, $filters, $order, $limit, $offset)->getQuery()->getResult();
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     * @throws ApiEntityNotFoundException
     * @throws ApiException
     * @throws ApiValidationException
     */
    public function post(array $parameters, Context $context)
    {
        /** @var Client $client */
        $client = $this->serializer->deserialize(json_encode($parameters), 'AppBundle\Entity\Client', 'json', $context);

        if (!($client instanceof Client) || $this->doctrine->getManager()->contains($client)) {
            throw new ApiEntityNotFoundException(array('client.error_save' => 'Client save error'), Response::HTTP_NOT_FOUND);
        }

        try {
            $client->init();
            $client = $this->clientService->save($client, $context);
        } catch (UniqueConstraintViolationException $e) {
            throw new ApiException(array('client.exists' => "Client already exists"), Response::HTTP_CONFLICT);
        } catch (Exception $e) {
            throw new ApiException($e->getMessages(), Response::HTTP_BAD_REQUEST);
        }

        return $client;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return mixed
     * @throws ApiEntityNotFoundException
     * @throws ApiException
     * @throws ApiValidationException
     */
    public function put(array $parameters, Context $context)
    {
        $client = $this->get($parameters, $context);
        if (empty($client)) {
            throw new ApiEntityNotFoundException(array('client.not_found' => 'Client not found'), Response::HTTP_NOT_FOUND);
        }

        /** @var Client $client */
        $client = $this->serializer->deserialize(json_encode($parameters), 'AppBundle\Entity\Client', 'json', $context);

        if (!($client instanceof Client) || !$this->doctrine->getManager()->contains($client)) {
            throw new ApiEntityNotFoundException(array('client.not_found' => 'Client not found'), Response::HTTP_NOT_FOUND);
        }

        try {
            $client = $this->clientService->save($client, $context);
        } catch (UniqueConstraintViolationException $e) {
            throw new ApiException(array('client.exists' => "Client already exists"), Response::HTTP_CONFLICT);
        } catch (Exception $e) {
            throw new ApiException($e->getMessages(), Response::HTTP_BAD_REQUEST);
        }

        return $client;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return bool
     */
    public function delete(array $parameters, Context $context)
    {
        $client = $this->get($parameters, $context);

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $manager->remove($client);
        $manager->flush();
        return true;
    }

}
