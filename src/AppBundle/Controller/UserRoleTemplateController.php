<?php

namespace AppBundle\Controller;

use AppBundle\Handler\UserRoleTemplateHandler;
use JMS\Serializer\DeserializationContext;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations as FOS;
use JMS\SecurityExtraBundle\Annotation\Secure;

class UserRoleTemplateController extends RestController
{
    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Apply or reapply role template on user",
     *  section="Users",
     *  statusCodes={
     *         204="Returned when the role template has been applyed or reapplied to the user",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when user or role template was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Put(
     *      "/api/users/{userId}/role_templates/{roleTemplateId}",
     *      requirements={
     *          "userId"="[0-9]+",
     *          "roleTemplateId"="[0-9]+"
     *      }
     * )
     *
     * @Secure(roles="ROLE_USER_ROLES_TEMPLATES")
     *
     * @param $userId
     * @param $roleTemplateId
     * @return \FOS\RestBundle\View\View
     */
    public function putAction($userId, $roleTemplateId)
    {
        $context = DeserializationContext::create();

        $params = array();
        $params['user_id'] = $userId;
        $params['role_template_id'] = $roleTemplateId;

        $this->getHandler()->put($params, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandler()
    {
        return $this->get(UserRoleTemplateHandler::HANDLER_NAME);
    }
}
