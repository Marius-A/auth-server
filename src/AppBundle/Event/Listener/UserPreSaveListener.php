<?php

namespace AppBundle\Event\Listener;

use AppBundle\Event\User\UserPreSaveEvent;

class UserPreSaveListener
{
    /**
     * @param UserPreSaveEvent $event
     */
    public function onUserPreSave(UserPreSaveEvent $event)
    {
        
    }
}
