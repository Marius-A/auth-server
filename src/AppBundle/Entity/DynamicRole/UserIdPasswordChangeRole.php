<?php

namespace AppBundle\Entity\DynamicRole;

use AppBundle\Entity\User;
use AppBundle\Service\RoleService;
use Symfony\Component\Security\Core\Role\RoleInterface;

class UserIdPasswordChangeRole implements RoleInterface
{
    /** @var  User */
    protected $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return RoleService::ROLE_USER_PASSWORD_CHANGE . "_" . strtoupper($this->user->getId());
    }
}