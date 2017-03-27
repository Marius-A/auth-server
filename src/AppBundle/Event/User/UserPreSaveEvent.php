<?php

namespace AppBundle\Event\User;

use AppBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class UserPreSaveEvent extends Event
{
    const NAME = 'user.pre.save';

    /** @var  User */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
