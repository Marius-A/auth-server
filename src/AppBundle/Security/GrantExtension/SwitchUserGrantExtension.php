<?php

namespace AppBundle\Security\GrantExtension;

use AppBundle\Entity\User;
use AppBundle\Server\OAuth2;
use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use OAuth2\Model\IOAuth2Client;
use OAuth2\OAuth2AuthenticateException;
use OAuth2\OAuth2ServerException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Switch user grant extension to get an access_token for the user intended to log with
 */
class SwitchUserGrantExtension implements GrantExtensionInterface
{
    const GRANT_TYPE_SWITCH_USER = "ee";

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Class construct
     * It has container injected because injecting Oauth server creates dependency
     *
     * @param UserProviderInterface $userProvider
     * @param ContainerInterface $container
     */
    public function __construct(UserProviderInterface $userProvider, ContainerInterface $container)
    {
        $this->userProvider = $userProvider;
        $this->container = $container;
    }

    /**
     *  {@inheritdoc}
     */
    public function checkGrantExtension(IOAuth2Client $client, array $inputData, array $authHeaders)
    {
        if (empty($inputData['username']) || empty($inputData['token'])) {
            throw new OAuth2ServerException(OAuth2::HTTP_BAD_REQUEST, OAuth2::ERROR_INVALID_GRANT, 'Missing parameters. "username" and "token" required');
        }

        $username = $inputData['username'];
        $token = $inputData['token'];

        $user = $this->authenticate($username, $token);

        return array(
            'data' => $user
        );
    }

    /**
     * @param string $username
     * @param string $token
     * @return User
     * @throws OAuth2ServerException
     */
    private function authenticate($username, $token)
    {
        try {
            /** @var User $user */
            $user = $this->userProvider->loadUserByUsername($username);

            /** @var OAuth2 $server */
            $server = $this->container->get('fos_oauth_server.server');
            $server->setScopeForceMode(false);
            $server->verifyAccessToken($token, 'allowed_to_switch');
            $server->setScopeForceMode(true);
        } catch(UsernameNotFoundException $ex) {
            throw new OAuth2ServerException(OAuth2::HTTP_UNAUTHORIZED, OAuth2::ERROR_INVALID_GRANT, "Unauthorized username");
        } catch(OAuth2AuthenticateException $ex) {
            throw new OAuth2ServerException(OAuth2::HTTP_UNAUTHORIZED, OAuth2::ERROR_INVALID_GRANT, "Unauthorized token");
        }

        return $user;
    }
}