<?php

namespace AppBundle\Handler;

use AppBundle\Entity\AuthProvider;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAuthProvider;
use AppBundle\Exception\ApiEntityNotFoundException;
use AppBundle\Exception\ApiException;
use AppBundle\Exception\Exception;
use AppBundle\Service\SerializerService;
use AppBundle\Service\UserAuthProviderService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use Symfony\Component\HttpFoundation\Response;

class UserAuthProviderHandler implements HandlerInterface
{
    /** @const */
    const HANDLER_NAME = 'user_auth_provider.handler';

    /** @var  Registry */
    protected $doctrine;

    /** @var  SerializerService */
    protected $serializer;

    /** @var  UserAuthProviderService */
    protected $userAuthProviderService;
    
    /** @var  UserHandler */
    protected $userHandler;
    
    /** @var  AuthProviderHandler */
    protected $authProviderHandler;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param SerializerService $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param UserAuthProviderService $userAuthProviderService
     */
    public function setUserAuthProviderService($userAuthProviderService)
    {
        $this->userAuthProviderService = $userAuthProviderService;
    }

    /**
     * @param UserHandler $userHandler
     */
    public function setUserHandler($userHandler)
    {
        $this->userHandler = $userHandler;
    }

    /**
     * @param AuthProviderHandler $authProviderHandler
     */
    public function setAuthProviderHandler($authProviderHandler)
    {
        $this->authProviderHandler = $authProviderHandler;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return \AppBundle\Entity\User|mixed
     * @throws ApiEntityNotFoundException
     * @throws ApiException
     */
    public function put(array $parameters, Context $context)
    {
        $userAuthProvider = $this->get($parameters, $context);

        $parameters['id'] = $userAuthProvider->getId();
        $parameters['encoder_name'] = $userAuthProvider->getEncoderName(empty($parameters['default_encoder'])) ?: $parameters['default_encoder'];

        /** @var UserAuthProvider $userAuthProvider */
        $userAuthProvider = $this->serializer->deserialize(json_encode($parameters), 'AppBundle\Entity\UserAuthProvider', 'json', $context);

        try {
            $userAuthProvider = $this->userAuthProviderService->save($userAuthProvider, $context);
        } catch (Exception $e) {
            throw new ApiException($e->getMessages(), Response::HTTP_BAD_REQUEST);
        }

        return $userAuthProvider;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return UserAuthProvider
     * @throws ApiEntityNotFoundException
     */
    public function get(array $parameters, Context $context)
    {
        $user = $this->userHandler->get(array('id' => $parameters['user_id']), $context);
        $authProvider = $this->authProviderHandler->get(array('id' => $parameters['auth_provider_id']), $context);

        $userAuthProvider = $this->doctrine->getRepository('AppBundle:UserAuthProvider')
            ->findOneBy(array('user' => $user->getId(), 'authProvider' => $authProvider->getId()));
        if (empty($userAuthProvider)) {
            throw new ApiEntityNotFoundException(array('user_auth_provider.not_found' => 'User authentication provider not found'), Response::HTTP_NOT_FOUND);
        }

        return $userAuthProvider;
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
     * @return UserAuthProvider
     * @throws ApiEntityNotFoundException
     * @throws ApiException
     * @throws Exception
     */
    public function post(array $parameters, Context $context)
    {
        $user = $this->userHandler->get(array('id' => $parameters['user_id']), $context);
        $authProvider = $this->authProviderHandler->get(array('id' => $parameters['auth_provider_id']), $context);

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();

        /** @var UserAuthProvider $userAuthProvider */
        $userAuthProvider = $this->serializer->deserialize(json_encode($parameters), 'AppBundle\Entity\UserAuthProvider', 'json', $context);

        try {
            $userAuthProvider = $this->userAuthProviderService->save($userAuthProvider, $context);
        } catch (UniqueConstraintViolationException $e) {
            $this->doctrine->getConnection()->rollback();
            $manager->close();
            throw new ApiException(array('user_auth_provider.exists' => "User auth provider already exists"), Response::HTTP_CONFLICT);
        } catch (Exception $e) {
            throw new ApiException($e->getMessages(), Response::HTTP_BAD_REQUEST);
        }

        return $userAuthProvider;
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