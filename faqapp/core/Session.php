<?php

class Session
{
    /**
     * Кладет элемент в сессию
     * @param $key
     * @param $value
     * @return bool
     */
    public static function Put($key, $value)
    {
        if (!empty($key) && !empty($value)) {
            $_SESSION[$key] = $value;
            return true;
        }
        return false;
    }

    /**
     * Добавляет элемент в сессию (как дополнительный элемент массива)
     * @param $key
     * @param $value
     * @return bool
     */
    public static function Add($key, $value)
    {
        if (!empty($key) && !empty($value)) {
            if (is_array($_SESSION[$key])) {
                $_SESSION[$key][] = $value;
            } elseif (isset($_SESSION[$key])) {
                $oldValue = $_SESSION[$key];
            }
            if (isset($_SESSION[$key])) {
                $_SESSION[$key][] = $oldValue;
            }
            $_SESSION[$key][] = $value;
            return true;

        }
        return false;
    }

    /**
     * Извлекает элемент из сессии
     * @param $key
     * @return mixed
     */
    public
    static function Get($key)
    {
        if (!empty($key) && isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return '';
    }

    /**
     * Извлекает и удаляет элемент из сессии
     * @param $key
     * @return mixed
     */
    public
    static function Flash($key)
    {
        if (!empty($key) && isset($_SESSION[$key])) {
            $result = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $result;
        }
        return '';
    }
}