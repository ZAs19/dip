<?php

abstract class BaseModel
{
    protected static $db = null; // подключение к базе данных
    protected static $dbTableName = ''; // название таблицы, с которой работает модель
    protected $user;
    protected $requiredFields;

    public function __construct()
    {
        if (!empty(Session::Get('user'))) {
            $this->user = Session::Get('user');
        }
    }

    /**
     * Ищет сущность по id
     * @param $id
     * @return mixed|null
     */
    public static function find($id)
    {
        $tableName = static::$dbTableName;
        $sql = "SELECT * FROM $tableName WHERE id = :id";
        $statement = self::getDB()->prepare($sql);
        $statement->bindParam('id', $id);
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Возвращает подключение в БД
     * @return PDO
     */
    protected static function getDB()
    {
        if (!is_resource(self::$db)) {
            self::$db = DataBase::connect();
        }
        return self::$db;
    }

    /**
     * Возвращает список всех пользователей
     * @return array
     */
    public static function all()
    {
        $tableName = static::$dbTableName;
        $sql = "SELECT * FROM $tableName ORDER BY name;";
        $statement = self::getDB()->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Возвращает название таблицы
     * @return string
     */
    public static function getDbTableName()
    {
        return static::$dbTableName;
    }

    /**
     * Удаляет сущность
     * @param $id
     * @return bool
     */
    public static function destroy($id)
    {
        $id = (int)$id;
        if (is_int($id)) {
            $tableName = static::$dbTableName;
            $sql = "DELETE FROM $tableName WHERE id = :id;";
            $statement = self::getDB()->prepare($sql);
            $statement->bindParam('id', $id);
            $result = $statement->execute();
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Ищет сущность по названию
     * @param $name
     * @return mixed|null
     */
    protected static function getItem($name)
    {
        $tableName = static::$dbTableName;
        $sql = "SELECT * FROM $tableName WHERE name = :name LIMIT 1";
        $statement = self::getDB()->prepare($sql);
        $statement->bindParam('name', $name);
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Проверяет, чтобы обязательные для модели параметры были не пусты
     * @param $params
     * @return bool
     */
    public function validate($params)
    {
        foreach ($params as $key => $param) {
            foreach ($this->requiredFields as $field) {
                if ($key === $field && empty($params[$key])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Возвращает имя пользователя (логин)
     * @return string|null
     */
    public function getUserName()
    {
        return $this->getCurrentUser('name');
    }

    /**
     * Возвращает текущего пользователя (если есть) или его параметр при наличии $param
     * @param null $param
     * @return null
     */
    protected function getCurrentUser($param = null)
    {
        if (isset($param)) {
            return isset($this->user[$param]) ? $this->user[$param] : null;
        }
        return isset($this->user) ? $this->user : null;
    }

    /**
     * Возвращает хеш md5 от полученного параметра
     * @param $password
     * @return string
     */
    public function getHash($password)
    {
        return md5($password);
    }
}