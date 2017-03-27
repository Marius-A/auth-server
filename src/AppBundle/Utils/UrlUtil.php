<?php

namespace AppBundle\Utils;

class UrlUtil
{
    public static function urlSafeB64Encode($data)
    {
        $b64 = base64_encode($data);
        $b64 = str_replace(array('+', '/', "\r", "\n", '='),
            array('-', '_'),
            $b64);

        return $b64;
    }

    public static function urlSafeB64Decode($b64)
    {
        $b64 = str_replace(array('-', '_'),
            array('+', '/'),
            $b64);

        return base64_decode($b64);
    }
}
