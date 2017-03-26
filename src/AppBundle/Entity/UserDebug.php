<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserDebug
 * @ORM\Entity
 * @ORM\Table(name="user_debug")
 */
class UserDebug
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="username", type="string", length=255, nullable=false)
     */
    protected $username;

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
}