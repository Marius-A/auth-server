<?php

namespace AppBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;

class UserFailedLoginAttemptEvent extends Event
{
    const NAME = 'user.login_attempt.failed';

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var int */
    protected $date;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->date = time();
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }
}