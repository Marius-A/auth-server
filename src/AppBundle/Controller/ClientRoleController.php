<?php

namespace AppBundle\Controller;


use AppBundle\Handler\ClientRoleHandler;
use FOS\RestBundle\Request\ParamFetcher;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as FOS;
use AppBundle\Controller\Annotations as Query;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\SecurityExtraBundle\Annotation\Secure;

class ClientRoleController extends RestController
{
    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a collection of roles",
     *  section="Clients",
     *  statusCodes={
     *         200="Returned when successful",
     *         400="Returned when request data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when client was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     *
     * @FOS\Get("/api/clients/{clientId}/roles")
     *
     * @Query\QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     roles={"ROLE_TEST"},
     *     default="10",
     *     description="our limit"
     * )
     * @Query\QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     nullable=true,
     *     default="0",
     *     description="our offset"
     * )
     *
     * @Secure(roles="ROLE_CLIENT_ROLES_LIST")
     *
     * @param ParamFetcher $paramFetcher
     * @param int $clientId
     * @return mixed
     */
    public function getClientRolesAction($clientId, ParamFetcher $paramFetcher)
    {
        $limit = (int) $paramFetcher->get('limit');
        $offset = (int) $paramFetcher->get('offset');
        $clientId = (int) $clientId;

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($this->getHandler()->clientAll($clientId, $limit, $offset));

        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Apply or reapply role on client",
     *  section="Clients",
     *  statusCodes={
     *         204="Returned when the role has been applied or reapplied to the client",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when client or role was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Put(
     *      "/api/clients/{clientId}/roles/{roleId}",
     *      requirements={
     *          "clientId"="[0-9]+",
     *          "roleId"="[0-9]+"
     *      }
     * )
     *
     * @Secure(roles="ROLE_CLIENT_ROLES_EDIT")
     *
     * @param $clientId
     * @param $roleId
     * @return \FOS\RestBundle\View\View
     */
    public function putClientRoleAction($clientId, $roleId)
    {
        $context = DeserializationContext::create();

        $params = array();
        $params['client_id'] = $clientId;
        $params['role_id'] = $roleId;

        $this->getHandler()->put($params, $context);
    }


    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Delete client role",
     *  section="Clients",
     *  statusCodes={
     *         204="Returned when an existing client role has been successfully deleted",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when client or role was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Delete(
     *      "/api/clients/{clientId}/roles/{roleId}",
     *      requirements={
     *          "clientId"="[0-9]+",
     *          "roleId"="[0-9]+"
     *      }
     * )
     *
     * @Secure(roles="ROLE_CLIENT_ROLES_DELETE")
     *
     * @param $clientId
     * @param $roleId
     */
    public function deleteClientRoleAction($clientId, $roleId)
    {
        $context = DeserializationContext::create();

        $params = array();
        $params['client_id'] = $clientId;
        $params['role_id'] = $roleId;

        $this->getHandler()->delete($params, $context);
    }

    /**
     * @return ClientRoleHandler
     */
    protected function getHandler()
    {
        return $this->get(ClientRoleHandler::HANDLER_NAME);
    }

}