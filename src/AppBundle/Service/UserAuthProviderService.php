<?php

namespace AppBundle\Service;

use AppBundle\Entity\AuthProvider;
use AppBundle\Entity\UserAuthProvider;
use AppBundle\Exception\Exception;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserAuthProviderService
{
    /** @var Registry */
    protected $doctrine;

    /** @var  ValidatorInterface */
    protected $validator;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param UserAuthProvider $userAuthProvider
     * @param Context $context
     * @return UserAuthProvider
     * @throws Exception
     */
    public function save(UserAuthProvider $userAuthProvider, Context $context = null)
    {
        $groups = null;
        if (!is_null($context) && $context->attributes->containsKey('groups')) {
            $groups = $context->attributes->get('groups')->get('value');
        }

        $violationList = $this->validator->validate($userAuthProvider, $groups);
        if ($violationList->count() > 0) {
            $exception = Exception::createFromConstraintViolationList($violationList);
            throw $exception;
        }

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $manager->beginTransaction();

        $this->doctrine->getManager()->persist($userAuthProvider);
        $this->doctrine->getManager()->flush();

        $manager->commit();

        return $userAuthProvider;
    }
}