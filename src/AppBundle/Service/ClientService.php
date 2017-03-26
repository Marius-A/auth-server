<?php

namespace AppBundle\Service;

use AppBundle\Entity\Client;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Exception\Exception;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class ClientService
{
    /** @const */
    const SERVICE_NAME = 'client.service';

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
     * Get client scopes. If requested scopes are provided, intersect client scopes with them
     *
     * @param Client $client
     * @param null $requestedScopes
     * @return array|string
     */
    public function getClientScopes(Client $client, $requestedScopes = null)
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $roleRepository = $manager->getRepository('AppBundle:Role');

        $roles = $client->getRoles();
        $requiredRoles = $client->getRequiredRoles();

        $requestedScopes = trim($requestedScopes);
        if (empty($requestedScopes)) {
            return "";
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

        $mergedRoles = array_merge($roles->toArray(), $requiredRoles->toArray());

        /** @var Role $role */
        foreach($mergedRoles as $role) {
            $childrenRoles = $roleRepository->children($role);
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
        $scopes = implode(" ", $scopes);

        return $scopes;
    }

    /**
     * Get client roles. If recursive param is set to true, return them recursively
     *
     * @param Client $client
     * @param bool $recursive
     * @return array
     */
    public function getClientRoles(Client $client, $recursive = true)
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $roleRepository = $manager->getRepository('AppBundle:Role');

        $unindexedRoles = array();
        $roles = $client->getRoles();
        foreach ($roles as $role) {
            /** @var Role $role */
            $unindexedRoles[] = $role;
            if ($recursive) {
                $unindexedRoles = array_merge($unindexedRoles, $roleRepository->children($role)); //array_merge resets indexes
            }
        }

        $result = array();
        foreach ($unindexedRoles as $role) {
            $result[$role->getId()] = $role;
        }

        return $result;
    }

    /**
     * @param Client $client
     * @param Context $context
     * @return mixed
     * @throws Exception
     */
    public function save(Client $client, Context $context)
    {
        $groups = null;
        if ($context->attributes->containsKey('groups')) {
            $groups = $context->attributes->get('groups')->get('value');
        }

        $violationList = $this->validator->validate($client, $groups);
        if ($violationList->count() > 0) {
            $exception = Exception::createFromConstraintViolationList($violationList);
            throw $exception;
        }

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $manager->beginTransaction();

        if (empty($client->getId())) {
            $username = str_replace(' ', '_', $client->getName());
            $username = strtolower($username);

            $clientUser = new User();
            $clientUser->setEmail($username);
            $clientUser->setUsername($username);
            $clientUser->setStatus(true);
            $manager->persist($clientUser);
            $client->setClientUser($clientUser);
        }

        $manager->persist($client);
        $manager->flush();

        $manager->commit();

        return $client;
    }

    /**
     * Apply role on client
     *
     * @param Client $client
     * @param Role $role
     */
    public function applyRoleOnClient(Client $client, Role $role)
    {
        $clientRoles = $client->getRoles(true);

        $found = false;
        foreach ($clientRoles as $clientRole) {
            if ($clientRole == $role){
                $found = true;
                break;
            }
        }
        if(!$found){
            $clientRoles->add($role);
        }

        $client->setRoles($clientRoles);
    }

    /**
     * Delete role on client
     *
     * @param Client $client
     * @param Role $role
     */
    public function deleteRoleOnClient(Client $client, Role $role)
    {
        $clientRoles = $client->getRoles(true);
        $clientRoles->removeElement($role);
        $client->setRoles($clientRoles);
    }

    /**
     * Apply required role on client
     *
     * @param Client $client
     * @param Role $role
     */
    public function applyRequiredRoleOnClient(Client $client, Role $role)
    {
        $clientRoles = $client->getRequiredRoles(true);

        $found = false;
        foreach ($clientRoles as $clientRole) {
            if ($clientRole == $role){
                $found = true;
                break;
            }
        }
        if(!$found){
            $clientRoles->add($role);
        }

        $client->setRequiredRoles($clientRoles);
    }

    /**
     * Delete required role on client
     *
     * @param Client $client
     * @param Role $role
     */
    public function deleteRequiredRoleOnClient(Client $client, Role $role)
    {
        $clientRoles = $client->getRequiredRoles(true);
        $clientRoles->removeElement($role);
        $client->setRequiredRoles($clientRoles);
    }
}
