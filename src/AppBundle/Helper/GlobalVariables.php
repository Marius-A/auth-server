<?php

namespace AppBundle\Helper;

class GlobalVariables {

    /**
     * Unique generated process id
     *@var string
     */
    public static $uuid = null;

    public static function generateUuid()
    {
        GlobalVariables::$uuid = uniqid("auth", true);
    }

}
