<?php

namespace AppBundle\Security\EntryPoint;

use AppBundle\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\OAuth2AuthenticateException;
use OAuth2\OAuth2;

class OAuthEntryPoint implements AuthenticationEntryPointInterface
{
    protected $serverService;

    /**
     * OAuthEntryPoint constructor.
     * @param OAuth2 $serverService
     */
    public function __construct(OAuth2 $serverService)
    {
        $this->serverService = $serverService;
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $exception = new OAuth2AuthenticateException(
            OAuth2::HTTP_UNAUTHORIZED,
            OAuth2::TOKEN_TYPE_BEARER,
            $this->serverService->getVariable(OAuth2::CONFIG_WWW_REALM),
            'access_denied',
            'OAuth2 authentication required'
        );
        $exception = ApiException::createFromOauth2ServerException($exception);

        return new Response(
            json_encode($exception->getMessages()),
            $exception->getCode(),
            array()
        );
    }
}
