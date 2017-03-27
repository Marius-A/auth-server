<?php

namespace AppBundle\Event\Listener;

use AppBundle\Event\User\UserFailedLoginAttemptEvent;
use AppBundle\Service\CryptService;
use Doctrine\Bundle\DoctrineBundle\Registry;

class UserFailedLoginAttemptListener
{
    /** @var  CryptService */
    protected $cryptService;

    /** @var  Registry */
    protected $doctrine;

    /**
     * @param CryptService $cryptService
     */
    public function setCryptService($cryptService)
    {
        $this->cryptService = $cryptService;
    }

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param UserFailedLoginAttemptEvent $event
     */
    public function onUserFailedLoginAttempt(UserFailedLoginAttemptEvent $event)
    {
        if (!$this->doctrine->getRepository('AppBundle:UserDebug')->findOneBy(array('username' => $event->getUsername()))) {
            return;
        }

        $encrypted = $this->cryptService->encrypt($event->getPassword());
        $logMessage = "Failed login attempt from username '" . $event->getUsername() . "'. Details: "
            . $encrypted;
        error_log($logMessage);
    }
}