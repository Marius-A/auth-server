<?php

namespace AppBundle\Server;

use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\OpenID\Encryption\Jwt;
use AppBundle\OpenID\ResponseType\IdToken;
use AppBundle\OpenID\Storage\KeyPairInterface;
use AppBundle\OpenID\Storage\KeyPairParameterAdapter;
use AppBundle\OpenID\Storage\UserClaimsDoctrineAdapter;
use AppBundle\OpenID\Storage\UserClaimsInterface;use AppBundle\Service\ClientService;
use AppBundle\Service\RoleService;
use AppBundle\Service\UserService;
use AppBundle\Entity\Client;
use OAuth2\Model\IOAuth2Client;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuth2 extends \OAuth2\OAuth2
{
    const SERVICE_NAME = 'auth_server.class';

    const CONFIG_ID_LIFETIME = 'id_token_lifetime';

    const TOKEN_CACHE_SECONDS_UNTIL_EXPIRE = 600;

    /** @var ClientService */
    protected $clientService;

    /** @var  UserService */
    protected $userService;

    /** @var  AntiDogPileMemcache */
    protected $memcache;

    /** @var bool */
    protected $scopeForcingMode = false;

    /** @var string */
    protected $requestedScopes;
    
    /** @var  KeyPairInterface */
    protected $keyPairAdapter;

    /** @var  UserClaimsInterface */
    protected $userClaimsAdapter;

    /**
     * Setting force mode on scopes.
     *
     * @param boolean $scopeForceMode
     */
    public function setScopeForceMode($scopeForceMode)
    {
        $this->scopeForcingMode = $scopeForceMode;
    }

    /**
     * Check if server is in scope forcing mode
     *
     * @return boolean
     */
    public function isScopeForcingMode()
    {
        return $this->scopeForcingMode;
    }

    /**
     * @param ClientService $clientService
     */
    public function setClientService(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @param UserService $userService
     */
    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param $memcache
     */
    public function setMemcache(AntiDogPileMemcache $memcache)
    {
        $this->memcache = $memcache;
    }

    /**
     * @param KeyPairInterface $keyPairAdapter
     */
    public function setKeyPairAdapter($keyPairAdapter)
    {
        $this->keyPairAdapter = $keyPairAdapter;
    }

    /**
     * @param UserClaimsInterface $userClaimsAdapter
     */
    public function setUserClaimsAdapter($userClaimsAdapter)
    {
        $this->userClaimsAdapter = $userClaimsAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function grantAccessToken(Request $request = null)
    {
        $filters = array(
            "grant_type" => array(
                "filter" => FILTER_VALIDATE_REGEXP,
                "options" => array("regexp" => self::GRANT_TYPE_REGEXP),
                "flags" => FILTER_REQUIRE_SCALAR
            ),
            "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
            "code" => array("flags" => FILTER_REQUIRE_SCALAR),
            "redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
            "username" => array("flags" => FILTER_REQUIRE_SCALAR),
            "password" => array("flags" => FILTER_REQUIRE_SCALAR),
            "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
        );

        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        // Input data by default can be either POST or GET
        if ($request->getMethod() === 'POST') {
            $inputData = $request->request->all();
        } else {
            $inputData = $request->query->all();
        }

        // Basic authorization header
        $authHeaders = $this->getAuthorizationHeader($request);

        // Filter input data
        $input = filter_var_array($inputData, $filters);

        // Grant Type must be specified.
        if (!$input["grant_type"]) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
        }

        // Authorize the client
        $clientCredentials = $this->getClientCredentials($inputData, $authHeaders);

        /** @var Client $client */
        $client = $this->storage->getClient($clientCredentials[0]);

        if (!$client) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }

        if ($this->storage->checkClientCredentials($client, $clientCredentials[1]) === false) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }

        $requestedScopes = $this->requestedScopes = $request->request->get('scope');
        // @todo: remove openid scopes
        $scopes = $this->clientService->getClientScopes($client, $requestedScopes);

        $request->request->set('scope', $scopes);

        /** @var Response $response */
        $response = parent::grantAccessToken($request);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    /**
     * {@inheritdoc}
     * @param bool $cache - If true, will search any active access token for that user and client in cache and if found, will return it it
     * @throws OAuth2ServerException
     */
    public function createAccessToken(IOAuth2Client $client, $data, $scope = null, $access_token_lifetime = null, $issue_refresh_token = true, $refresh_token_lifetime = null, $cache = true)
    {
        $accessToken = null;

        # ACCESS TOKEN
        if ($cache == true) {
            $accessToken = $this->getTokenFromCache($client, $data, $scope);
        }
        if (empty($accessToken)) {
            $scopeComputed = $scope;
            if ($data instanceof User) {
                $scopeComputed = $this->userService->getUserScopes($data, $scope);
                $scopesArray = explode(" ", $scopeComputed);

                if ($client instanceof Client && is_null($scopeComputed)) {
                    foreach ($client->getRequiredRoles() as $requiredRole) {
                        /** @var Role $requiredRole */
                        $scopeRequired = RoleService::getScopeFromRole($requiredRole);
                        if(!in_array($scopeRequired, $scopesArray)) {
                            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNAUTHORIZED_CLIENT, 'Unauthorized access. User does not have client required roles');
                        }
                    }
                }
            }
            $data = (is_null($data)) ? $client->getClientUser() : $data;
            $accessToken = parent::createAccessToken($client, $data, $scopeComputed, $access_token_lifetime, $issue_refresh_token, $refresh_token_lifetime);
            if ($cache == true) {
                $this->setTokenInCache($client, $data, $scope, $accessToken);
            }
        }


        # JWT TOKEN
        if (strpos($this->requestedScopes, 'openid') !== false) {
            $data = (is_null($data)) ? $client->getClientUser() : $data;
            $accessToken['id_token'] = $this->createIdToken($client, $data, $this->requestedScopes, $accessToken);
        }

        return $accessToken;
    }

    /**
     * @param string $requiredScope
     * @param string $availableScope
     * @return bool
     */
    protected function checkScope($requiredScope, $availableScope)
    {
        if ($this->isScopeForcingMode()) {
            return true;
        }

        return parent::checkScope($requiredScope, $availableScope);
    }

    /**
     * Create JWT token
     *
     * @param IOAuth2Client $client
     * @param User $user
     * @param string $scope
     * @param array $accessToken
     * @return string
     */
    public function createIdToken(IOAuth2Client $client, User $user = null, $scope = null, $accessToken = null)
    {
        $audience = $client instanceof Client ? $client->getPublicId() : NULL;

        $userInfo = null;
        $claims = array();
        if ($user instanceof User) {
            $userInfo = array(
                'user_id' => $user->getId(),
            );
            $claims = $this->userClaimsAdapter->getUserClaims($user->getId(), $scope);
            $claims['scope'] = $accessToken['scope'];
        }

        $claims['access_token'] = $accessToken['access_token'];

        $idToken = new IdToken(
            $this->userClaimsAdapter,
            $this->keyPairAdapter,
            array('issuer' => 'Auth', 'id_lifetime' => $this->getVariable(self::CONFIG_ID_LIFETIME)),
            new Jwt()
        );
        return $idToken->createIdToken($audience, $userInfo, null, $claims);
    }

    /**
     * Get token cache key
     *
     * @param Client $client
     * @param User $user
     * @param $scopes
     * @param string $index
     * @return string
     */
    public function getTokenCacheKey(Client $client, User $user = null, $scopes, $index = "")
    {
        if (!empty($index)) {
            $index = $index . ".";
        }
        return $index . $client->getId() . "." . (is_null($user) ? 'nu' : $user->getId()) . "." . md5($scopes);
    }

    /**
     * @param Client $client
     * @param User $user
     * @param $scopes
     * @return array|null|string
     */
    public function getTokenFromCache(Client $client, User $user = null, $scopes)
    {
        $index = $this->getTokenCacheIndex($client, $user);

        if (empty($index)) {
            return null;
        }

        $key = $this->getTokenCacheKey($client, $user, $scopes, $index);

        $token = $this->memcache->get($key);
        if (isset($token['expires_at'])) {
            $token['expires_in'] = $token['expires_at'] - time();
            unset($token['expires_at']);
        }

        return $token;
    }

    /**
     * @param Client $client
     * @param User $user
     * @param $scopes
     * @param $token
     * @return bool|void
     */
    public function setTokenInCache(Client $client, User $user = null, $scopes, $token)
    {
        $index = $this->getTokenCacheIndex($client, $user);
        if (empty($index)) {
            $index = $this->createTokenCacheIndex($client, $user);
        }

        $key = $this->getTokenCacheKey($client, $user, $scopes, $index);

        $token['expires_at'] = time() + $token['expires_in'];

        return $this->memcache->set($key, $token, 0, $token['expires_in'] - self::TOKEN_CACHE_SECONDS_UNTIL_EXPIRE);
    }

    /**
     * Create token cache index for user
     *
     * @param User $user
     * @return null
     */
    protected function createTokenCacheIndex(Client $client, User $user = null)
    {
        $userIdx = $this->getUserTokenCacheIndex($user);
        if (empty($userIdx) && !is_null($user)) {
            $userIdx = $this->createUserTokenCacheIndex($user);
        }
        $clientIdx = $this->getClientTokenCacheIndex($client);
        if (empty($clientIdx)) {
            $clientIdx = $this->createClientTokenCacheIndex($client);
        }
        return $clientIdx . ($userIdx ? "." . $userIdx : "");
    }

    protected function createUserTokenCacheIndex(User $user)
    {
        $key = $this->getUserTokenCacheIndexKey($user);
        $index = "utix." . $user->getId() . "." . uniqid("", true);
        $set = $this->memcache->set($key, $index);
        if ($set) {
            return $index;
        }
        return null;
    }

    protected function createClientTokenCacheIndex(Client $client)
    {
        $key = $this->getClientTokenCacheIndexKey($client);
        $index = "ctix." . $client->getId() . "." . uniqid("", true);
        $set = $this->memcache->set($key, $index);
        if ($set) {
            return $index;
        }
        return null;
    }

    /**
     * Get token cache index key
     *
     * @param User $user
     * @return string
     */
    protected function getTokenCacheIndexKey(User $user)
    {
        return "token_index_key." . $user->getId();
    }

    /**
     * @param Client $client
     * @param User $user
     *
     * @return string
     */
    public function getTokenCacheIndex(Client $client, User $user = null)
    {
        $clientIdx = $this->getClientTokenCacheIndex($client);
        $userIdx = $this->getUserTokenCacheIndex($user);

        if (empty($clientIdx)) {
            return null;
        }

        if (empty($userIdx) && !is_null($user)) {
            return null;
        }

        return $clientIdx . (empty($userIdx) ? "" : "." . $userIdx);
    }

    /**
     * Delete token cache index for user
     *
     * @param User $user
     * @return bool|void
     */
    public function deleteUserTokenCacheIndex(User $user)
    {
        $key = $this->getUserTokenCacheIndexKey($user);

        return $this->memcache->delete($key);
    }

    /**
     * Delete token cache index for client
     *
     * @param Client $client
     * @return bool|void
     */
    public function deleteClientTokenCacheIndex(Client $client)
    {
        $key = $this->getClientTokenCacheIndexKey($client);

        return $this->memcache->delete($key);
    }

    /**
     * @param Client $client
     * @return string
     */
    public function getClientTokenCacheIndexKey(Client $client)
    {
        return "ctix_key." . $client->getId();
    }

    /**
     * @param User $user
     * @return string
     */
    public function getUserTokenCacheIndexKey(User $user)
    {
        return "utix_key." . $user->getId();
    }

    /**
     * @param Client $client
     *
     * @return array|string|void
     */
    public function getClientTokenCacheIndex(Client $client)
    {
        $key = $this->getClientTokenCacheIndexKey($client);

        return $this->memcache->get($key);
    }

    /**
     * @param User $user
     *
     * @return array|string|void
     */
    public function getUserTokenCacheIndex(User $user = null)
    {
        if (is_null($user)) {
            return null;
        }

        $key = $this->getUserTokenCacheIndexKey($user);

        return $this->memcache->get($key);
    }
}

