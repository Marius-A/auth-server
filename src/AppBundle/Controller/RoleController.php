<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Role;
use AppBundle\Handler\RoleHandler;
use AppBundle\Service\ParamFetcherService;
use FOS\RestBundle\Controller\Annotations as FOS;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

class RoleController extends RestController
{
    /**
     * @FOS\View()
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a collection of Roles",
     *  section="Roles",
     *  statusCodes={
     *         200="Returned when successful",
     *         400="Returned when request data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested user is not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     default="10",
     *     description="our limit"
     * )
     * @FOS\QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     nullable=true,
     *     default="0",
     *     description="our offset"
     * )
     *
     * @FOS\Get("/api/roles")
     *
     * @Secure(roles="ROLE_ROLES_LIST")
     *
     * @param ParamFetcherService $paramFetcher
     * @return Response
     */
    public function getRolesAction(ParamFetcherService $paramFetcher)
    {
        $limit = $paramFetcher->get('limit');
        $offset = $paramFetcher->get('offset');

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($this->getHandler()->all(array(), $limit, $offset));

        return $this->handleView($view);
    }

    /**
     * @FOS\View()
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a Role by id",
     *  section="Roles",
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested Role is not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\Get("/api/roles/{roleId}")
     *
     * @Secure(roles="ROLE_ROLES_VIEW")
     *
     * @param $roleId
     * @return Response
     */
    public function getRoleAction($roleId)
    {
        $parameters = array('id' => $roleId);
        /** @var Role $role */
        $role = $this->getHandler()->get($parameters, $this->getDeserializationContext());

        if (is_null($role)) {
            throw new NotFoundHttpException();
        }

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($role);

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandler()
    {
        return $this->get(RoleHandler::HANDLER_NAME);
    }
}
