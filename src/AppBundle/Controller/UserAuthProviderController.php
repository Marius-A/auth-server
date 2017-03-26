<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AccessToken;
use AppBundle\Entity\AuthProvider;
use AppBundle\Entity\Client;
use AppBundle\Exception\ApiEntityConflictException;
use AppBundle\Handler\UserAuthProviderHandler;
use AppBundle\Server\OAuth2;
use AppBundle\Service\ParamFetcherService;
use AppBundle\Controller\Annotations as Auth;
use AppBundle\Service\RoleService;
use AppBundle\Service\UserService;
use FOS\RestBundle\Controller\Annotations as FOS;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAuthProviderController extends RestController
{
    /**
     * @ApiDoc(
     *  resource=false,
     *  description="Change user password on authentication provider",
     *  section="Users",
     *  statusCodes={
     *         200="Returned when password was successfully changed for the user",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when user or auth provider was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Put(
     *     "/api/users/{userId}/auth_provider/{authProviderId}/password",
     *     requirements={
     *          "userId"="[0-9]+",
     *          "authProviderId"="[0-9]+"
     *     })
     * )
     *
     * @Auth\RequestParam(
     *      name="password",
     *      requirements=".{5,}",
     *      nullable=false,
     *      allowBlank=false,
     *      description="User's new password",
     *      exceptionCode="user.password.invalid"
     * )
     * @Auth\RequestParam(
     *      name="confirm_password",
     *      requirements=".{5,}",
     *      nullable=false,
     *      allowBlank=false,
     *      description="User's new password confirmed",
     *      exceptionCode="user.confirm_password.invalid"
     * )
     * @Auth\RequestParam(
     *      name="old_password",
     *      requirements=".{5,}",
     *      nullable=true,
     *      allowBlank=true,
     *      description="User's old password",
     *      exceptionCode="user.old_password.invalid"
     * )
     *
     * @param ParamFetcherService $paramFetcher
     * @param int $userId
     * @param int $authProviderId
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     * @throws \AppBundle\Exception\ApiException
     */
    public function putPasswordAction(ParamFetcherService $paramFetcher, $userId, $authProviderId = AuthProvider::MAIN_PROVIDER_ID, Request $request)
    {
        # CONTEXT SETUP
        $authorizationService = $this->container->get('security.authorization_checker');
        $context = $this->getContext();
        if (!$authorizationService->isGranted(RoleService::ROLE_USERS_EDIT)) {
            $this->denyAccessUnlessGranted(RoleService::ROLE_USER_PASSWORD_CHANGE . "_" . $userId, null, "Access denied");
        }
        if (
            !$authorizationService->isGranted(RoleService::ROLE_USER_FORGOT_PASSWORD_CHANGE)
            && !$authorizationService->isGranted(RoleService::ROLE_USERS_EDIT)
        ) {
            $context->addGroup(RoleService::GROUP_OLD_PASSWORD);
        }
        $this->context->addGroup(RoleService::GROUP_USER_PASSWORD_CHANGE);

        # GET CLIENT
        /** @var OAuth2 $server */
        $server = $this->get('fos_oauth_server.server');
        $bearerToken = $server->getBearerToken($request);
        $tokenStorage = $this->get('fos_oauth_server.storage');
        /** @var AccessToken $bearerToken */
        $bearerToken = $tokenStorage->getAccessToken($bearerToken);
        /** @var Client $client */
        $client = $bearerToken->getClient();

        # DATA SETUP
        $params = array();
        $params['user_id'] = $userId;
        $params['auth_provider_id'] = $authProviderId;
        $params['password'] = $paramFetcher->get('password', true);
        $params['confirm_password'] = $paramFetcher->get('confirm_password', true);
        $params['old_password'] = $paramFetcher->get('old_password', true);
        $params['default_encoder'] = $client->getDefaultEncoder();

        $this->getHandler()->put($params, $this->getDeserializationContext());

        return $this->handleView($this->view(null, Response::HTTP_OK));
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Create user auth provider",
     *  section="Users",
     *  statusCodes={
     *         201="Returned when auth provider has been granted for user",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when user or auth provider was not found",
     *         409="Returned when user is already granted that auth provider",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Post(
     *     "/api/users/{userId}/auth_provider/{authProviderId}",
     *     requirements={
     *          "userId"="[0-9]+",
     *          "authProviderId"="[0-9]+"
     *     })
     *
     * @Auth\RequestParam(
     *      name="identifier",
     *      requirements=".{1,}",
     *      nullable=false,
     *      allowBlank=false,
     *      description="User auth provider identifier",
     *      exceptionCode="user_auth_provider.identifier.invalid"
     * )
     *
     * @Secure(roles="ROLE_USER_AUTH_PROVIDER_ADD")
     *
     * @param ParamFetcherService $paramFetcher
     * @param $userId
     * @param int $authProviderId
     * @return Response
     */
    public function postUserAuthProviderAction(ParamFetcherService $paramFetcher, $userId, $authProviderId = AuthProvider::MAIN_PROVIDER_ID)
    {
        $statusCode = Response::HTTP_CREATED;
        $context = $this->getDeserializationContext();

        # DATA SETUP
        $params = array();
        $params['user_id'] = $userId;
        $params['auth_provider_id'] = $authProviderId;
        $params['identifier'] = $paramFetcher->get('identifier', true);

        try {
            $userAuthProvider = $this->getHandler()->post($params, $context);
        } catch (ApiEntityConflictException $e) {
            $userAuthProvider = $e->getConflictEntity();
            $statusCode = $e->getStatusCode();
        }

        return $this->handleView($this->view(null, $statusCode));
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Return a user auth provider by user id and auth provider id",
     *  section="Users",
     *  statusCodes={
     *         200="Returned when successful",
     *         400="Returned when request data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when user or auth provider was not found",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Get(
     *     "/api/users/{userId}/auth_provider/{authProviderId}",
     *     requirements={
     *          "userId"="[0-9]+",
     *          "authProviderId"="[0-9]+"
     *     }
     * )
     *
     * @Secure(roles="ROLE_USER_AUTH_PROVIDER_VIEW")
     *
     * @param ParamFetcherService $paramFetcher
     * @param $userId
     * @param int $authProviderId
     * @return mixed
     */
    public function getUserAuthProviderAction(ParamFetcherService $paramFetcher, $userId, $authProviderId = AuthProvider::MAIN_PROVIDER_ID)
    {
        $context = $this->getDeserializationContext();

        $params = array();
        $params['user_id'] = $userId;
        $params['auth_provider_id'] = $authProviderId;

        $userAuthProvider = $this->getHandler()->get($params, $context);

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($userAuthProvider);

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandler()
    {
        return $this->get(UserAuthProviderHandler::HANDLER_NAME);
    }
}
