<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AccessToken;
use AppBundle\Exception\ApiException;
use AppBundle\Exception\Exception;
use AppBundle\Server\OAuth2;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\Serializer\SerializationContext;
use OAuth2\OAuth2AuthenticateException;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as FOS;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Controller\Annotations as Auth;

class TokenController extends FOSRestController
{
    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Creates a new Token using various grant types",
     *  section="Token",
     *  statusCodes={
     *         201="Returned when a new token is created",
     *         400="Returned when you have a validation error",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @Auth\RequestParam(
     *      name="client_id",
     *      requirements="^[0-9]+\_[a-zA-Z0-9]+$",
     *      nullable=false,
     *      allowBlank=false,
     *      description="Client id",
     *      grantTypes={"all"}
     * )
     * @Auth\RequestParam(
     *      name="client_secret",
     *      requirements="^[a-zA-Z0-9]+$",
     *      nullable=false,
     *      allowBlank=false,
     *      description="Client secret",
     *      grantTypes={"all"}
     * )
     * @Auth\RequestParam(
     *      name="grant_type",
     *      requirements=".{1,}",
     *      nullable=false,
     *      allowBlank=false,
     *      description="Grant type",
     *      grantTypes={"all"}
     * )
     * @Auth\RequestParam(
     *      name="username",
     *      requirements="^[a-zA-Z.0-9-_]{3,}$",
     *      nullable=true,
     *      allowBlank=true,
     *      description="Username",
     *      grantTypes={
     *          "password",
     *          AppBundle\Security\GrantExtension\SwitchUserGrantExtension::GRANT_TYPE_SWITCH_USER
     *      }
     * )
     * @Auth\RequestParam(
     *      name="password",
     *      requirements="^.{5,}$",
     *      nullable=true,
     *      allowBlank=true,
     *      description="Password",
     *      grantTypes={
     *          "password"
     *      }
     * )
     * @Auth\RequestParam(
     *      name="token",
     *      requirements="^.{1,}$",
     *      nullable=true,
     *      allowBlank=true,
     *      description="A valid access token",
     *      grantTypes={
     *          AppBundle\Security\GrantExtension\SwitchUserGrantExtension::GRANT_TYPE_SWITCH_USER
     *      }
     * )
     * @Auth\RequestParam(
     *      name="refresh_token",
     *      requirements="^.{1,}$",
     *      nullable=true,
     *      allowBlank=true,
     *      description="A valid refresh token",
     *      grantTypes={
     *          "refresh_token"
     *      }
     * )
     *
     * @FOS\Post("/oauth/v2/token")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function postTokenAction(Request $request)
    {
        /** @var OAuth2 $server */
        $server = $this->get('fos_oauth_server.server');
        //$server->setScopeForceMode(true);

        try {
            $token = $server->grantAccessToken($request);

            return $token;
        } catch (OAuth2ServerException $e) {
            $e = ApiException::createFromOauth2ServerException($e);
            throw $e;
        }
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Introspects a Token",
     *  section="Token",
     *  statusCodes={
     *          200="Returned when a token has been successfully introspected. Invalid tokens are introspected as inactive ones",
     *          401="Returned when token is invalid",
     *          403="Returned when token has invalid grand",
     *          500="Returned when an internal server error occurred"
     *     }
     * )
     * @Auth\RequestParam(
     *      name="token",
     *      requirements="^.{1,}$",
     *      nullable=true,
     *      allowBlank=true,
     *      description="Token to be introspected",
     * )
     *
     * @FOS\Post("/oauth/v2/introspection")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postIntrospectionAction(Request $request)
    {
        $tokenParam = $request->get('token');
        /** @var OAuth2 $server */
        $server = $this->get('fos_oauth_server.server');

        $view = $this->view(null, Response::HTTP_OK);
        $context = new Context();
        $context->setSerializeNull(true);
        $view->setContext($context);

        $token = new AccessToken();
        $token->setToken($tokenParam);

        try {
            $token = $server->verifyAccessToken($token->getToken());
            $context->addGroup("token_introspect_all");
        } catch(OAuth2AuthenticateException $e) {
            $token = new AccessToken();
            $context->addGroup("token_introspect_active");
        }
        $view->setContext($context);
        $view->setData($token);

        return $this->handleView($view);
    }

    public function getHandler()
    {

    }
}
