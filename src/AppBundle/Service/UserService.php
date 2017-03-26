<?php

namespace AppBundle\Service;

use AppBundle\Entity\Role;
use AppBundle\Entity\RoleTemplate;
use AppBundle\Entity\User;
use AppBundle\Event\User\UserPreSaveEvent;
use AppBundle\Exception\Exception;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class UserService
{
    const SERVICE_NAME = 'user.service';

    /** @var Registry */
    protected $doctrine;

    /** @var  EncoderFactory */
    protected $encoderFactory;

    /** @var RecursiveValidator */
    protected $validator;

    /** @var  EntityService */
    protected $entityService;
    
    /** @var  EventDispatcher */
    protected $eventDispatcher;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param EncoderFactory $encoderFactory
     */
    public function setEncoderFactory(EncoderFactory $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param RecursiveValidator $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param EntityService $entityService
     */
    public function setEntityService($entityService)
    {
        $this->entityService = $entityService;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get user scopes. If requested scopes are provided, intersect user scopes with them.
     * This function is children aware and will intersect also with children scopes.
     * @param User $user
     * @param string|null $requestedScopes
     * @return string
     */
    public function getUserScopes(User $user, $requestedScopes = null)
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $roleRepository = $manager->getRepository('AppBundle:Role');

        $roles = $user->getRoles(true);

        $requestedScopes = trim($requestedScopes);
        if (empty($requestedScopes)) {
            $requestedScopes = array();
        } else {
            $requestedScopes = explode(" ", $requestedScopes);
        }

        $requestedRoles = array();
        foreach($requestedScopes as $requestedScope) {
            $requestedRole = "ROLE_" . strtoupper($requestedScope);
            $requestedRole = $roleRepository->findOneBy(array('role' => $requestedRole));
            if (!$requestedRole instanceof Role) {
                continue;
            }
            $requestedRoles[$requestedRole->getId()] = $requestedRole;
            $childrenRoles = $roleRepository->children($requestedRole);
            foreach($childrenRoles as $childrenRole) {
                /** @var Role $childrenRole */
                $requestedRoles[$childrenRole->getId()] = $childrenRole;
            }
        }

        $scopes = array();
        foreach($roles as $role) {
            /** @var Role $role */
            $childrenRoles = $roleRepository->getChildren($role);
            $childrenRoles[] = $role;
            foreach ($childrenRoles as $childrenRole) {
                if (!empty($requestedScopes) && !isset($requestedRoles[$childrenRole->getId()])) {
                    continue;
                }

                $scope = RoleService::getScopeFromRole($childrenRole);
                if (!empty($scope)) {
                    $scopes[$childrenRole->getRole()] = $scope;
                }
            }
        }

        $dynamicRoles = $user->getDynamicRoles();
        foreach ($dynamicRoles as $dynamicRole) {
            /** @var RoleInterface $dynamicRole */
            $scope = RoleService::getScopeFromRole($dynamicRole);
            if (!empty($scope)) {
                $scopes[$dynamicRole->getRole()] = $scope;
            }
        }

        $scopes = implode(" ", $scopes);

        return $scopes;
    }

    /**
     * @return string
     */
    public function getUserRandomSalt()
    {
        return base64_encode(openssl_random_pseudo_bytes(120));
    }

    /**
     * @param User $user
     * @param Context $context
     * @return User
     * @throws Exception
     */
    public function save(User $user, Context $context = null)
    {
        $this->eventDispatcher->dispatch(UserPreSaveEvent::NAME, new UserPreSaveEvent($user));
        
        $groups = null;
        if (!is_null($context) && $context->attributes->containsKey('groups')) {
            $groups = $context->attributes->get('groups')->get('value');
        }

        $violationList = $this->validator->validate($user, $groups);
        if ($violationList->count() > 0) {
            /** @var Exception $exception */
            $exception = Exception::createFromConstraintViolationList($violationList);
            throw $exception;
        }

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $manager->beginTransaction();

        $this->doctrine->getManager()->persist($user);

        $this->doctrine->getManager()->flush();

        $manager->commit();

        return $user;
    }

    /**
     * Apply role template on user by setting all template roles to user
     *
     * @param $user
     * @param $roleTemplate
     */
    public function applyRoleTemplateOnUser(User $user, RoleTemplate $roleTemplate)
    {
        $roleTemplates = $user->getRoleTemplates();

        $found = false;
        foreach ($roleTemplates as $value) {
            if ($value == $roleTemplate) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $roleTemplates->add($roleTemplate);
            $user->setRoleTemplates($roleTemplates);
        }

        $templateRoles = $roleTemplate->getRoles();
        $userRoles = $user->getRoles(true);
        foreach ($templateRoles as $templateRole) {
            $found = false;
            foreach ($userRoles as $userRole) {
                if ($userRole == $templateRole) {
                    $found = true;
                }
            }
            if (!$found) {
                $userRoles->add($templateRole);
            }
        }
        $user->setRoles($userRoles);
    }

    /**
     * Apply role on user
     *
     * @param User $user
     * @param Role $role
     */
    public function applyRoleOnUser(User $user, Role $role)
    {
        $userRoles = $user->getRoles(true);

        $found = false;
        foreach ($userRoles as $userRole) {
            if ($userRole == $role){
                $found = true;
                break;
            }
        }
        if(!$found){
            $userRoles->add($role);
        }

        $user->setRoles($userRoles);
    }

    /**
     * Delete role on user
     *
     * @param User $user
     * @param Role $role
     */
    public function deleteRoleOnUser(User $user, Role $role)
    {
        $userRoles = $user->getRoles(true);
        $userRoles->removeElement($role);
        $user->setRoles($userRoles);
    }
}
