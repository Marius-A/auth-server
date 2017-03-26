<?php

namespace AppBundle\OpenID\ResponseType;

interface IdTokenInterface extends ResponseTypeInterface
{
    /**
     * Create the id token.
     *
     * If Authorization Code Flow is used, the id_token is generated when the
     * authorization code is issued, and later returned from the token endpoint
     * together with the access_token.
     * If the Implicit Flow is used, the token and id_token are generated and
     * returned together.
     *
     * @param string $clientId     The client id.
     * @param string $userId       The user id.
     * @param string $nonce        OPTIONAL The nonce.
     * @param string $userClaims   OPTIONAL Claims about the user.
     * @param string $access_token OPTIONAL The access token, if known.
     *
     * @return string The ID Token represented as a JSON Web Token (JWT).
     *
     * @see http://openid.net/specs/openid-connect-core-1_0.html#IDToken
     */
    public function createIdToken($clientId, $userId, $nonce = null, $userClaims = null, $access_token = null);
}
