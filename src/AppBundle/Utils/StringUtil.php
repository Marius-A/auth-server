<?php

namespace AppBundle\Utils;

class StringUtil
{
    public static function toSnakeCase($str)
    {
        return str_replace(array('.', '@'), '_', $str);
    }

    public static function randomString($length)
    {
        $result = "";
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $charArray = str_split($chars);
        for($i = 0; $i < $length; $i++){
            $randItem = array_rand($charArray);
            $result .= "".$charArray[$randItem];
        }
        return $result;
    }

    public static function toCamelCase($str)
    {
        $str = strtolower($str);
        return preg_replace_callback('/[_\-]+([a-z])/', create_function('$c', 'return strtoupper($c[1]);'), $str);
    }
}
