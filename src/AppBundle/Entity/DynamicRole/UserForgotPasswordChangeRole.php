<?php

namespace AppBundle\Entity\DynamicRole;

use AppBundle\Service\RoleService;
use Symfony\Component\Security\Core\Role\RoleInterface;

class UserForgotPasswordChangeRole implements RoleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return RoleService::ROLE_USER_FORGOT_PASSWORD_CHANGE;
    }
}