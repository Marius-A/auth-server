<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use AppBundle\Event\User\UserFailedLoginAttemptEvent;
use AppBundle\OpenID\Encryption\Jwt;
use AppBundle\OpenID\Storage\KeyPairInterface;
use FOS\OAuthServerBundle\Storage\OAuthStorage;
use JMS\Serializer\SerializationContext;
use OAuth2\Model\IOAuth2Client;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthStorageService extends OAuthStorage
{
    /** @var  EventDispatcher */
    protected $eventDispatcher;

    /**
     * @var KeyPairInterface
     */
    protected $keyPairAdapter;

    /**
     * @param KeyPairInterface $keyPairAdapter
     */
    public function setKeyPairAdapter($keyPairAdapter)
    {
        $this->keyPairAdapter = $keyPairAdapter;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param IOAuth2Client $client
     * @param string $username
     * @param string $password
     * @return array|bool
     */
    public function checkUserCredentials(IOAuth2Client $client, $username, $password)
    {
        $data = null;
        try {
            $data = parent::checkUserCredentials($client, $username, $password);
        } catch (UsernameNotFoundException $ex) {
           $data = false;
        }

        if ($data === false) {
            $this->eventDispatcher->dispatch(UserFailedLoginAttemptEvent::NAME, new UserFailedLoginAttemptEvent($username, $password));
        }

        return $data;
    }

    public function getAccessToken($token)
    {
        if (preg_match(':^[a-zA-Z0-9-_]+\.[a-zA-Z0-9-_]+\.[a-zA-Z0-9-_]+$:', $token)) {
            $jwt = new Jwt();
            $openId = $jwt->decode(
                $token,
                $this->keyPairAdapter->getPublicKey()
            );
            $token = $openId['access_token'];
        }
        return parent::getAccessToken($token);
    }
}