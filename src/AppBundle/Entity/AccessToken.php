<?php

namespace AppBundle\Entity;

use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;
use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Model\ClientInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 *
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="scope",
 *          column=@ORM\Column(
 *              name     = "scope",
 *              type     = "string",
 *              length   = 6000,
 *              nullable = true
 *          )
 *      )
 * })
 *
 * @ORM\Table(name="access_token")
 */
class AccessToken extends BaseAccessToken
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $user;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * {@inheritdoc}
     */
    public function hasExpired()
    {
        if (empty($this->expiresAt)) {
            return true;
        }
        return parent::hasExpired();
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return !$this->hasExpired();
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }
}
