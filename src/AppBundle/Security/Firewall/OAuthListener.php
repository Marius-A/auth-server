<?php

namespace AppBundle\Security\Firewall;

use AppBundle\Exception\ApiException;
use AppBundle\OpenID\Encryption\Jwt;
use AppBundle\OpenID\Storage\KeyPairInterface;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;


class OAuthListener extends \FOS\OAuthServerBundle\Security\Firewall\OAuthListener
{
    /** @var  KeyPairInterface */
    protected $keyPairAdapter;

    /**
     * @param GetResponseEvent $event
     * @throws ApiException
     * @return mixed
     * @throws \OAuth2\OAuth2AuthenticateException
     */
    public function handle(GetResponseEvent $event)
    {
        if (null === $oauthToken = $this->serverService->getBearerToken($event->getRequest())) {
            return null;
        }

        /** @var OAuthToken $token */
        $token = $this->getToken($oauthToken);

        try {
            $returnValue = $this->authenticationManager->authenticate($token);

            if ($returnValue instanceof TokenInterface) {
                return $this->securityContext->setToken($returnValue);
            }

            if ($returnValue instanceof Response) {
                return $event->setResponse($returnValue);
            }
        } catch (AuthenticationException $e) {
            $p = $e->getPrevious();
            if (null !== $p) {
                throw new ApiException(array($p->getMessage() => $p->getDescription()), Response::HTTP_FORBIDDEN);
            }
        }

        return null;
    }

    /**
     * @param string $oauthToken
     *
     * @return OAuthToken
     */
    protected function getToken($oauthToken)
    {
        $token = new OAuthToken();
        if (preg_match(':^[a-zA-Z0-9-_]+\.[a-zA-Z0-9-_]+\.[a-zA-Z0-9-_]+$:', $oauthToken)) {
            $jwt = new Jwt();
            $openId = $jwt->decode(
                $oauthToken,
                $this->keyPairAdapter->getPublicKey()
            );
            $token->setToken($openId['access_token']);
        } else {
            $token->setToken($oauthToken);
        }

        return $token;
    }

    /**
     * @param KeyPairInterface $keyPairAdapter
     */
    public function setKeyPairAdapter($keyPairAdapter)
    {
        $this->keyPairAdapter = $keyPairAdapter;
    }
}
