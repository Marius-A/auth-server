<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AccessToken;
use AppBundle\Entity\Client;
use AppBundle\Handler\UserRoleHandler;
use FOS\RestBundle\Request\ParamFetcher;
use AppBundle\Server\OAuth2;
use FOS\RestBundle\Controller\Annotations as FOS;
use JMS\Serializer\DeserializationContext;
use AppBundle\Controller\Annotations as Query;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\SecurityExtraBundle\Annotation\Secure;

class UserRoleController extends RestController
{
    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a collection of roles",
     *  section="Users",
     *  statusCodes={
     *         200="Returned when successful",
     *         400="Returned when request data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when user was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     *
     * @FOS\Get("/api/users/{userId}/roles")
     *
     * @FOS\QueryParam(
     *     name="clientIntersect",
     *     requirements="\d+",
     *     nullable=true,
     *     default="0",
     *     description="If set to 1 return User Roles intersected with Client Roles"
     * )
     * @FOS\QueryParam(
     *     name="recursive",
     *     requirements="\d+",
     *     nullable=true,
     *     default="1",
     *     description="If set to 1 return User Role recursively"
     * )
     *
     * @Secure(roles="ROLE_USER_ROLES_LIST")
     *
     * @param int $userId
     * @param ParamFetcher $paramFetcher
     * @param Request $request
     * @return mixed
     */
    public function getUserRolesAction($userId, ParamFetcher $paramFetcher, Request $request)
    {
        $clientIntersect = (int) $paramFetcher->get('clientIntersect');
        $recursive = (int) $paramFetcher->get('recursive');
        $userId = (int) $userId;

        $client = null;
        if ($clientIntersect) {
            /** @var OAuth2 $server */
            $server = $this->get('fos_oauth_server.server');
            $bearerToken = $server->getBearerToken($request);

            $tokenStorage = $this->get('fos_oauth_server.storage');
            /** @var AccessToken $bearerToken */
            $bearerToken = $tokenStorage->getAccessToken($bearerToken);
            /** @var Client $client */
            $client = $bearerToken->getClient();
        }

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($this->getHandler()->userAll($userId, $client, $recursive));

        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Apply or reapply role on user",
     *  section="Users",
     *  statusCodes={
     *         204="Returned when the role has been applied or reapplied to the user",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when user or role was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Put(
     *      "/api/users/{userId}/roles/{roleId}",
     *      requirements={
     *          "userId"="[0-9]+",
     *          "roleId"="[0-9]+"
     *      }
     * )
     *
     * @Secure(roles="ROLE_USER_ROLES_EDIT")
     *
     * @param $userId
     * @param $roleId
     * @return \FOS\RestBundle\View\View
     */
    public function putUserRoleAction($userId, $roleId)
    {
        $context = DeserializationContext::create();

        $params = array();
        $params['user_id'] = $userId;
        $params['role_id'] = $roleId;

        $this->getHandler()->put($params, $context);
    }


    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Delete user role",
     *  section="Users",
     *  statusCodes={
     *         204="Returned when an existing user role has been successfully deleted",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when user or role was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Delete(
     *      "/api/users/{userId}/roles/{roleId}",
     *      requirements={
     *          "userId"="[0-9]+",
     *          "roleId"="[0-9]+"
     *      }
     * )
     *
     * @Secure(roles="ROLE_USER_ROLES_DELETE")
     *
     * @param $userId
     * @param $roleId
     */
    public function deleteUserRoleAction($userId, $roleId)
    {
        $context = DeserializationContext::create();

        $params = array();
        $params['user_id'] = $userId;
        $params['role_id'] = $roleId;

        $this->getHandler()->delete($params, $context);
    }

    /**
     * @return UserRoleHandler
     */
    protected function getHandler()
    {
        return $this->get('app_bundle.userRole.handler');
    }
}