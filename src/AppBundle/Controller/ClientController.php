<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Annotations as Auth;
use AppBundle\Handler\ClientHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Controller\Validator\Constraint as AuthControllerConstraint;
use FOS\RestBundle\Controller\Annotations as FOS;
use AppBundle\Controller\Annotations as Query;
use JMS\SecurityExtraBundle\Annotation\Secure;

class ClientController extends RestController
{
    /**
     * @FOS\View()
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a collection of Clients",
     *  section="Clients",
     *  statusCodes={
     *         200="Returned when successful",
     *         400="Returned when request data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when requested client is not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     * @Auth\QueryOrderBag(name="order",
     *  queryParams={
     *      @Auth\QueryOrderParam(
     *          name="id",
     *          description="order client id"
     *      ),
     *      @Auth\QueryOrderParam(
     *          name="name",
     *          description="order client name"
     *      )
     *  },
     *  default="id+")
     *
     * @Auth\QueryParamBag(name="filters",
     *  queryParams={
     *      @Auth\QueryParam(
     *          name="name",
     *          modifiers={"^","$","*"},
     *          description="filter by name",
     *          roles={"ROLE_ADMIN"}
     *      ),
     *      @Auth\QueryParam(
     *          name="created",
     *          modifiers={">","<"},
     *          description="filter by crated"
     *      )
     *  },
     *  description="List of Param (name followed by one of the allowed modifiers)",
     *  nullable=true
     * )
     *
     * @Auth\QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     roles={"ROLE_TEST"},
     *     default="10",
     *     description="our limit"
     * )
     * @Auth\QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     nullable=true,
     *     default="0",
     *     description="our offset"
     * )
     *
     * @FOS\Get("/api/clients")
     *
     * @Secure(roles="ROLE_CLIENTS_LIST")
     *
     * @param ParamFetcherInterface $paramFetcher
     * @return mixed
     */
    public function getClientsAction(ParamFetcherInterface $paramFetcher)
    {
        try {
            $limit = $paramFetcher->get('limit', true);
            $offset = $paramFetcher->get('offset', true);
            $filters = $paramFetcher->get('filters', true);
            $order = $paramFetcher->get('order', true);
        } catch (\Exception $e) {
            return $this->handleView($this->view(array('errors' => $e->getMessage()), Response::HTTP_BAD_REQUEST));
        }
        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($this->getHandler()->all($filters, $order, $limit, $offset));
        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Retrieves a client by id",
     *  output = "AppBundle\Entity\Client",
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
     * @FOS\View()
     * @FOS\Get("/api/clients/{clientId}")
     *
     * @Secure(roles="ROLE_CLIENT_VIEW")
     *
     * @param int $clientId The Client's id
     * @return mixed
     */
    public function getClientAction($clientId)
    {
        $parameters = array('id' => $clientId);
        $client = $this->getHandler()->get($parameters, $this->getDeserializationContext());

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($client);

        return $this->handleView($view);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Creates a new Client",
     *  section="Clients",
     *  statusCodes={
     *         201="Returned when a new client is created",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested client is not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Post("/api/clients")
     *
     * @Secure(roles="ROLE_CLIENTS_ADD")
     *
     * @param Request $request
     * @return array|\FOS\RestBundle\View\View
     */
    public function postClientAction(Request $request)
    {
        $context = $this->getDeserializationContext();
        $client = $this->getHandler()->post($request->request->all(), $context);

        $routeOptions = array(
            'clientId' => $client->getId(),
            '_format' => $request->get('_format'),
        );
        return $this->routeRedirectView(
            'get_client',
            $routeOptions,
            Response::HTTP_CREATED
        );
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Replaces an existing Client",
     *  section="Clients",
     *  statusCodes={
     *         204="Returned when an existing Client has been successfully updated",
     *         400="Returned when the posted data is invalid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested client is not found",
     *         500="Returned when an internal server error occurred"
     *  }
     * )
     *
     * @FOS\View()
     * @FOS\Put("/api/clients/{clientId}")
     *
     * @Secure(roles="ROLE_CLIENTS_EDIT")
     *
     * @param Request $request
     * @param $clientId
     * @return array|\FOS\RestBundle\View\View|null
     */
    public function putClientAction(Request $request, $clientId)
    {
        $context = $this->getDeserializationContext();

        $request->request->add(array('id' => $clientId));
        $client = $this->getHandler()->put($request->request->all(), $context);

        $routeOptions = array(
            'clientId' => $client->getId(),
            '_format' => $request->get('_format')
        );
        return $this->routeRedirectView('get_client', $routeOptions, Response::HTTP_NO_CONTENT);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Deletes an existing Client",
     *  section="Clients",
     *  requirements={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="the id of the Client to delete"
     *      }
     *  },
     *  statusCodes={
     *         204="Returned when an existing Client has been successfully deleted",
     *         400="Returned when request data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested Client is not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Delete("/api/clients/{clientId}")
     *
     * @Secure(roles="ROLE_CLIENT_DELETE")
     *
     * @param $clientId
     */
    public function deleteClientAction($clientId)
    {
        $parameters = array('id' => $clientId);
        $this->getHandler()->delete($parameters, $this->getDeserializationContext());
    }

    /**
     * @return ClientHandler
     */
    protected function getHandler()
    {
        return $this->get(ClientHandler::HANDLER_NAME);
    }
}
