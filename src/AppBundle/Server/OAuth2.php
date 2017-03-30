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
}

