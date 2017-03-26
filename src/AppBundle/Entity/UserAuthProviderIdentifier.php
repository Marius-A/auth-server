<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserAuthProviderIdentifierRepository")
 * @ORM\Table(name="user_auth_provider_identifier")
 */
class UserAuthProviderIdentifier
{

    /**
     * @var UserAuthProvider
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\UserAuthProvider")
     * @ORM\JoinColumn(name="user_auth_provider_id", referencedColumnName="id")
     */
    protected $userAuthProvider;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="identifier", type="string", nullable=false, length=255)
     */
    protected $identifier;

    /**
     * UserAuthProviderIdentifier constructor.
     * @param $identifier
     */
    public function __construct($identifier = null)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return UserAuthProvider
     */
    public function getUserAuthProvider()
    {
        return $this->userAuthProvider;
    }

    /**
     * @param UserAuthProvider $userAuthProvider
     */
    public function setUserAuthProvider($userAuthProvider)
    {
        $this->userAuthProvider = $userAuthProvider;
    }
}
