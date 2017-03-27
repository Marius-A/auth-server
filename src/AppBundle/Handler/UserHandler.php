<?php

namespace AppBundle\Handler;

use AppBundle\Exception\ApiEntityConflictException;
use AppBundle\Exception\ApiEntityNotFoundException;
use AppBundle\Exception\ApiException;
use AppBundle\Exception\Exception;
use AppBundle\Repository\UserRepository;
use AppBundle\Entity\User;
use AppBundle\Service\UserService;
use AppBundle\Service\Interfaces\FilterInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Validator\Validator;

class UserHandler implements HandlerInterface
{
    const HANDLER_NAME = 'user.handler';

    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var EncoderFactory
     */
    protected $securityEncoderFactory;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var FilterInterface
     */
    protected $userFilterService;

    /**
     * @param SerializerInterface $serializer
     * @param Registry $doctrine
     */
    public function __construct(SerializerInterface $serializer, Registry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->repository = $this->doctrine->getRepository('AppBundle:User');
        $this->serializer = $serializer;
    }

    /**
     * @param EncoderFactory $securityEncoderFactory
     */
    public function setSecurityEncoderFactory($securityEncoderFactory)
    {
        $this->securityEncoderFactory = $securityEncoderFactory;
    }

    /**
     * @param Validator $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param UserService $userService
     */
    public function setUserService($userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param FilterInterface $userFilterService
     */
    public function setUserFilterService(FilterInterface $userFilterService)
    {
        $this->userFilterService = $userFilterService;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @throws ApiEntityNotFoundException
     * @return User
     */
    public function get(array $parameters, Context $context)
    {
        $user = $this->doctrine->getRepository('AppBundle:User')->find($parameters['id']);
        if (empty($user)) {
            throw new ApiEntityNotFoundException(array('user.not_found' => 'User not found'), Response::HTTP_NOT_FOUND);
        }
        return $user;
    }

    /**
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws ApiException
     */
    public function all($filters = array(), $limit = 10, $offset = 0)
    {
        try{
            $queryBuilder = $this->doctrine->getRepository('AppBundle:User')->createQueryBuilder('u');
            $users = $this->userFilterService->apply($queryBuilder, $filters, null, $limit, $offset)->getQuery()->getResult();

            return $users;
        } catch (DBALException $e) {
            $apiException = new ApiException('Get operation failed', null, $e);
            $apiException->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            throw $apiException;
        }
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return bool
     */
    public function delete(array $parameters, Context $context)
    {
        $user = $this->get($parameters, $context);

        if (empty($user)) {
            return false;
        }

        /** @var EntityManager $em */
        $em =  $this->doctrine->getManager();
        $em->remove($user);
        $em->flush();

        return true;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return User|mixed
     * @throws ApiEntityNotFoundException
     * @throws ApiException
     * @throws Exception
     */
    public function put(array $parameters, Context $context)
    {
        $user = $this->get($parameters, $context);
        if (empty($user)) {
            throw new ApiEntityNotFoundException(array('user.not_found' => 'User not found'), Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->serializer->deserialize(json_encode($parameters), 'AppBundle\Entity\User', 'json', $context);

        if (!($user instanceof User) || !$this->doctrine->getManager()->contains($user)) {
            throw new ApiEntityNotFoundException(array('user.not_found' => 'User not found'), Response::HTTP_NOT_FOUND);
        }

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $this->doctrine->getConnection()->beginTransaction();
        try {
            $user = $this->userService->save($user, $context);

            $manager->persist($user);
            $manager->flush();

            $this->doctrine->getConnection()->commit();
        } catch (UniqueConstraintViolationException $e) {
            $this->doctrine->getConnection()->rollback();
            $manager->close();
            throw new ApiException(array('user.exists' => "User already exists"), Response::HTTP_CONFLICT);
        } catch (Exception $e) {
            $this->doctrine->getConnection()->rollback();
            $manager->close();
            throw new ApiException($e->getMessages(), Response::HTTP_BAD_REQUEST);
        }

        return $user;
    }

    /**
     * @param array $parameters
     * @param Context $context
     * @return User|mixed
     * @throws ApiException
     */
    public function post(array $parameters, Context $context)
    {
        /** @var User $user */
        $user = $this->serializer->deserialize(json_encode($parameters), 'AppBundle\Entity\User', 'json', $context);

        try {
            $user = $this->userService->save($user, $context);
        } catch (UniqueConstraintViolationException $e) {
            /** @var EntityManager $manager */
            $manager = $this->doctrine->getManager();
            $qb = $manager->getRepository('AppBundle:User')->createQueryBuilder("u");
            $qbWhere = $qb->expr()->eq("u.email", ":email");
            if (!empty($parameters['username'])) {
                $qbWhere = $qb->expr()->orX(
                    $qb->expr()->eq("u.username", ":username"),
                    $qbWhere
                );
                $qb->setParameter("username", $parameters['username']);
            }
            $qb->andWhere($qbWhere);
            $qb->setParameter("email", $parameters['email']);

            $user = $qb->getQuery()->getSingleResult();

            throw new ApiEntityConflictException(array('user.exists' => "User already exists"), Response::HTTP_CONFLICT, $e, $user);
        } catch (Exception $e) {
            throw new ApiException($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $user;
    }

    /**
     * @return UserRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}