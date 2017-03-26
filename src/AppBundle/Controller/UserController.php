<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AccessToken;
use AppBundle\Entity\Client;
use AppBundle\Entity\DynamicRole\UserForgotPasswordChangeRole;
use AppBundle\Entity\User;
use AppBundle\Exception\ApiEntityConflictException;
use AppBundle\Exception\ApiException;
use AppBundle\Handler\UserHandler;
use AppBundle\Helper\Email;
use AppBundle\Helper\GlobalVariables;
use AppBundle\Server\OAuth2;
use AppBundle\Service\EmailService;
use AppBundle\Service\ParamFetcherService;
use AppBundle\Service\RoleService;
use FOS\RestBundle\Controller\Annotations as FOS;
use AppBundle\Controller\Annotations as Query;
use AppBundle\Controller\Annotations as Auth;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

class UserController extends RestController
{
    /**
     * @FOS\Get("/api/users")
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a collection of Users",
     *  section="Users",
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
     * @Query\QueryParamBag(name="filters",
     *  queryParams={
     *      @Query\QueryParam(
     *          name="username",
     *          modifiers={"^","$","*"},
     *          description="filter by username"
     *      ),
     *      @Query\QueryParam(
     *          name="email",
     *          modifiers={"=","*"},
     *          description="filter by email"
     *      ),
     *      @Query\QueryParam(
     *          name="status",
     *          modifiers={"="},
     *          description="filter by status"
     *      ),
     *      @Query\QueryParam(
     *          name="role_id",
     *          modifiers={"="},
     *          description="filter by role_id"
     *      ),
     *      @Query\QueryParam(
     *          name="id",
     *          modifiers={"="},
     *          description="filter by id"
     *      ),
     *  },
     *  description="List of Param (name followed by one of the allowed modifiers)",
     *  nullable=true
     * )
     *
     * @Query\QueryParam(
     *     name="limit",
     *     requirements="\d+",
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
     * @Secure(roles="ROLE_USERS_LIST")
     *
     * @param ParamFetcherService $paramFetcher
     * @return Response
     */
    public function getUsersAction(ParamFetcherService $paramFetcher)
    {
        $filters = $paramFetcher->get('filters', true);
        $limit = $paramFetcher->get('limit', true);
        $offset = $paramFetcher->get('offset', true);

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($this->getHandler()->all($filters, $limit, $offset));

        return $this->handleView($view);
    }

    /**
     * @FOS\Get("/api/users/{userId}")
     * @ApiDoc(
     *  resource=true,
     *  description="Returns an User by id",
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
     * @Secure(roles="ROLE_USER_VIEW")
     * @param $userId
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getUserAction($userId)
    {
        $parameters = array('id' => $userId);

        /** @var User $user */
        $user = $this->getHandler()->get($parameters, $this->getDeserializationContext());

        if (is_null($user)) {
            throw new NotFoundHttpException();
        }

        $view = $this->view(null, Response::HTTP_OK);
        $view->setData($user);
        return $this->handleView($view);
    }

    /**
     * @FOS\Post("/api/users")
     * @ApiDoc(
     *  resource=true,
     *  description="Creates a new User",
     *  section="Users",
     *  statusCodes={
     *         201="Returned when a new user is created",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested user is not found",
     *         409="Returned when username or email already exist",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     *
     * @Auth\RequestParam(
     *      name="username",
     *      requirements="^[a-zA-Z.0-9-_]{3,}$",
     *      nullable=true,
     *      allowBlank=false,
     *      description="User's username",
     *      exceptionCode="user.username.invalid"
     * )
     * @Auth\RequestParam(
     *      name="email",
     *      requirements="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}",
     *      description="User's email",
     *      exceptionCode="user.email.invalid",
     *      nullable=false,
     *      allowBlank=false
     * )
     * @Secure(roles="ROLE_USERS_ADD")
     * @param ParamFetcherService $paramFetcher
     * @return \FOS\RestBundle\View\View
     */
    public function postUserAction(ParamFetcherService $paramFetcher, Request $request)
    {
        $statusCode = Response::HTTP_CREATED;
        $context = $this->getDeserializationContext();

        try {
            /** @var OAuth2 $server */
            $server = $this->get('fos_oauth_server.server');
            $bearerToken = $server->getBearerToken($request);

            $tokenStorage = $this->get('fos_oauth_server.storage');
            /** @var AccessToken $bearerToken */
            $bearerToken = $tokenStorage->getAccessToken($bearerToken);
            $clientId = $bearerToken->getClient()->getId();

            $params = $paramFetcher->allButNull(true);
            $params['requesting_client_id'] = $clientId;

            /** @var User $user */
            $user = $this->getHandler()->post($params, $context);
        } catch (ApiEntityConflictException $e) {
            $user = $e->getConflictEntity();
            $statusCode = $e->getStatusCode();
        }

        $routeOptions = array(
            'userId' => $user->getId()
        );
        return $this->routeRedirectView(
            'get_user',
            $routeOptions,
            $statusCode
        );
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Change user information",
     *  section="Users",
     *  statusCodes={
     *         204="Returned when an existing User has been successfully updated",
     *         400="Returned when the posted data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested user is not found",
     *         409="Returned when username or email already exist",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Put("/api/users/{userId}")
     *
     * @Auth\RequestParam(
     *      name="username",
     *      requirements="^[a-zA-Z.0-9-_]{3,}$",
     *      nullable=true,
     *      allowBlank=true,
     *      description="User's username",
     *      exceptionCode="user.username.invalid"
     * )
     * @Auth\RequestParam(
     *      name="email",
     *      requirements="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}",
     *      nullable=true,
     *      allowBlank=true,
     *      description="User's email",
     *      exceptionCode="user.email.invalid"
     * )
     * @Secure(roles="ROLE_USERS_EDIT")
     * @param ParamFetcherService $paramFetcher
     * @param $userId
     * @return \FOS\RestBundle\View\View
     */
    public function putUserAction(ParamFetcherService $paramFetcher, $userId)
    {
        $context = $this->getDeserializationContext();

        $params = $paramFetcher->allButNull(true);
        $params['id'] = $userId;
        if (isset($params['email'])) {
            $params['email'] = empty($params['email']) ? null : $params['email'];
        }
        if (isset($params['username'])) {
            $params['username'] = empty($params['username']) ? null : $params['username'];
        }

        $user = $this->getHandler()->put($params, $context);

        $routeOptions = array(
            'userId' => $user->getId()
        );
        return $this->routeRedirectView(
            'get_user',
            $routeOptions,
            Response::HTTP_NO_CONTENT
        );
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Removes an User by id",
     *  section="Users",
     *  statusCodes={
     *         204="Returned when an existing User has been successfully deleted",
     *         400="Returned when request data is not valid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         404="Returned when the requested user is not found",
     *         500="Returned when an internal server error occurred"
     *      }
     * )
     *
     * @FOS\View()
     * @FOS\Delete("/api/users/{userId}")
     * @Secure(roles="ROLE_USER_DELETE")
     * @param $userId
     * @return array
     */
    public function deleteUserAction($userId)
    {
        $parameters = array('id' => $userId);

        $isRemoved = $this->getHandler()->delete($parameters, $this->getDeserializationContext());

        if ($isRemoved) {
            $view = $this->view(null, Response::HTTP_NO_CONTENT);

            return $view;
        }

        $view = $this->view(null, Response::HTTP_NOT_FOUND);
        $view->setData(
            array(
                "error" => array(
                    sprintf("User %s not found!", $userId)
                )
            )
        );

        return $view;
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Returns an User by token",
     *  section="Users",
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     *
     * @FOS\View()
     * @FOS\Get("/api/me")
     *
     * @return Response
     */
    public function getMeAction()
    {
        $this->getContext()->addGroup("ROLE_USER_ME");

        $view = $this->view(null, Response::HTTP_OK);
        $user = $this->getUser();
        $view->setData($user);
        return $this->handleView($view);
    }


    /**
     * @FOS\Post("/api/password_reset")
     *
     * @ApiDoc(
     *  resource=false,
     *  description="Reset password by email",
     *  section="Users",
     *  statusCodes={
     *         200="Returned when successful",
     *         400="Returned when the posted data is invalid",
     *         401="Returned when token is invalid",
     *         403="Returned when token has invalid grand",
     *         500="Returned when an internal server error occurred"
     *     }
     * )
     * @Auth\RequestParam(
     *     name="email",
     *     nullable=false,
     *     description="User's email",
     *     exceptionCode="user.email.invalid"
     * )
     *
     * @Secure(roles="ROLE_USER_FORGOT_PASSWORD")
     *
     * @param ParamFetcherService $paramFetcher
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     * @throws ApiException
     */
    public function postUserForgotPasswordAction(ParamFetcherService $paramFetcher, Request $request)
    {
        $emailAddress = $paramFetcher->get('email', true);

        /** @var User $user */
        $user = $this->getHandler()->getRepository()->findOneBy(array('email' => $emailAddress));
        if (is_null($user)) {
            throw new ApiException(array('user.not_found' => 'User not found'), Response::HTTP_BAD_REQUEST);
        }

        /** @var OAuth2 $server */
        $server = $this->get('fos_oauth_server.server');
        $bearerToken = $server->getBearerToken($request);

        $tokenStorage = $this->get('fos_oauth_server.storage');
        /** @var AccessToken $bearerToken */
        $bearerToken = $tokenStorage->getAccessToken($bearerToken);
        /** @var Client $client */
        $client = $bearerToken->getClient();

        $user->getDynamicRoles()->add(new UserForgotPasswordChangeRole());

        $accessToken = $server->createAccessToken(
            $client,
            $user,
            RoleService::getScopeFromRoleName(RoleService::ROLE_USER_PASSWORD_CHANGE) . "_" . $user->getId() .
            ' ' .
            RoleService::getScopeFromRoleName(RoleService::ROLE_USER_FORGOT_PASSWORD_CHANGE) .
            ' ' .
            RoleService::getScopeFromRoleName(RoleService::ROLE_USER_ME)
            ,
            null,
            false,
            null
        );

        $email = new Email();
        $emailService = $this->get(EmailService::SERVICE_NAME);

        if (strpos($client->getPasswordResetUrl(), '{token}') === false) {
            throw new ApiException(array('client.invalid_reset_password_url' => 'Incorrect client reset url'), Response::HTTP_NOT_IMPLEMENTED);
        }

        $resetUrl = str_replace('{token}', $accessToken['access_token'], $client->getPasswordResetUrl());

        try {
            $body = $emailService->getTwig()->render(
                'AppBundle:Email:UserResetPasswordBody.html.twig',
                array('username' => $user->getUsername(), 'reset_url' => $resetUrl)
            );
        } catch (\Exception $e) {
            throw new ApiException(array('auth-server.reset_password_template_not_found' => "Reset password template not found"), Response::HTTP_NOT_IMPLEMENTED);
        }

        $email->setTo($emailAddress)
            ->setFrom($client->getDefaultEmail())
            ->setSubject('Password reset for your ' . $client->getName() . ' account')
            ->setBody($body)
            ->setId(GlobalVariables::$uuid);

        $emailService->sendMail($emailService::DEFAULT_FUNCTION_METHOD, $email);

        return $this->handleView($this->view('OK', Response::HTTP_OK));
    }

    /**
     * {@inheritdoc}
     * @return UserHandler
     */
    protected function getHandler()
    {
        return $this->get(UserHandler::HANDLER_NAME);
    }
}
