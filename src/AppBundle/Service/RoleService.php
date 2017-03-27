<?php

namespace AppBundle\Service;

use AppBundle\Entity\Role;
use AppBundle\Exception\Exception;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class RoleService
{
    const SERVICE_NAME = 'role.service';

    const ROLE_USER_PASSWORD_CHANGE = 'ROLE_USER_PASSWORD_CHANGE';
    const ROLE_USER_FORGOT_PASSWORD_CHANGE = 'ROLE_USER_FORGOT_PASSWORD_CHANGE';
    const ROLE_USER_ME = 'ROLE_USER_ME';
    const ROLE_USER_ROLES_EDIT = 'ROLE_USER_ROLES_EDIT';
    const ROLE_USERS_EDIT = "ROLE_USERS_EDIT";
    const ROLE_USER_AUTH_PROVIDER_ADD = "ROLE_USER_AUTH_PROVIDER_ADD";

    const GROUP_OLD_PASSWORD = "GROUP_OLD_PASSWORD";
    const GROUP_USER_PASSWORD_CHANGE = 'GROUP_USER_PASSWORD_CHANGE';

    /** @var Registry */
    protected $doctrine;

    /** @var RecursiveValidator */
    protected $validator;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param RecursiveValidator $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * Return scope from a provided role object
     *
     * @param RoleInterface $role
     * @return string
     */
    public static function getScopeFromRole(RoleInterface $role)
    {
        $roleName = $role->getRole();
        if (empty($roleName)) {
            return;
        }
        $scope = explode("ROLE_", $roleName);
        if (!isset($scope[1])) {
            return;
        }

        return strtolower($scope[1]);
    }

    /**
     * Return scope from a provided role name
     *
     * @param string $roleName
     * @return string
     */
    public static function getScopeFromRoleName($roleName)
    {
        if (empty($roleName)) {
            return;
        }
        $scope = explode("ROLE_", $roleName);
        if (!isset($scope[1])) {
            return;
        }

        return strtolower($scope[1]);
    }

    /**
     * @param array $roles
     * @return array
     */
    public static function getRolesNames($roles)
    {
        $rolesArray = array();
        foreach ($roles as $role) {
            $rolesArray[] = $role->getRole();
        }
        return $rolesArray;
    }

    /**
     * @param Role $role
     * @param Context $context
     * @return Role
     * @throws Exception
     */
    public function save(Role $role, Context $context = null)
    {
        $groups = null;
        if (!is_null($context) && $context->attributes->containsKey('groups')) {
            $groups = $context->attributes->get('groups')->get('value');
        }

        $violationList = $this->validator->validate($role, $groups);
        if ($violationList->count() > 0) {
            /** @var Exception $exception */
            $exception = Exception::createFromConstraintViolationList($violationList);
            throw $exception;
        }

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $manager->beginTransaction();

        $this->doctrine->getManager()->persist($role);
        $this->doctrine->getManager()->flush();

        $manager->commit();

        return $role;
    }
}