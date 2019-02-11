<?php

class BlackList extends BaseModel
{
    protected static $dbTableName = 'faq_black_list';

    public static function getBlackList()
    {
        $list = [];
        foreach (self::all() as $item) {
            $list[] = $item['name'];
        }
        return $list;
    }

    /**
     * Добавляет в БД список ключевых слов
     * @param $arrayItems
     * @return bool
     */
    public function setItems($arrayItems)
    {
        $this->delete();
        $tableName = self::getDbTableName();
        $result = false;
        foreach ($arrayItems as $blackPhrase) {
            if (!empty($blackPhrase)) {
                $sql = "INSERT INTO $tableName (name, user_id, created_at, updated_at) 
                        VALUES (:blackPhrase, :user_id, NOW(), NOW())";
                $statement = self::getDB()->prepare($sql);
                $statement->bindParam('blackPhrase', $blackPhrase);
                $statement->bindParam('user_id', $this->getCurrentUser('id'));
                $result = ($statement->execute() or $result);
            }
        }
        return $result;
    }

    /**
     * Очищает черный список
     * @return bool
     */
    protected function delete()
    {
        $tableName = self::getDbTableName();
        $sql = "DELETE FROM $tableName";
        return self::getDB()->prepare($sql)->execute();
    }
}