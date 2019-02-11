<?php

class Request
{
    public static function has($name)
    {
        if (isset($_REQUEST[$name])) {
            return true;
        }
        return false;
    }

    public static function get($name)
    {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }
        return '';
    }
}