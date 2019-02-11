<?php

class DataBase
{
    /**
     * Подключение к базе данных mysql
     * @param $host string адрес
     * @param $dbname string название базы
     * @param $user string пользователь
     * @param $pass string пароль
     * @return PDO
     */
    public static function connect($host = DB_HOST, $dbname = DB_DATABASE, $user = DB_USERNAME, $pass = DB_PASSWORD)
    {
        try {
            $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        } catch (PDOException $e) {
            die('Database error: ' . $e->getMessage() . '<br/>');
        }
        return $db;
    }
}