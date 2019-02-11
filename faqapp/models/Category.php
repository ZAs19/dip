<?php

class Category extends BaseModel
{
    protected static $dbTableName = 'faq_categories';

    /**
     * Удаляет категорию и все вопросы, которые были связаны с этой категорией
     * @param $id
     * @return bool
     */
    public static function destroy($id)
    {
        $id = (int)$id;
        $parentResult = parent::destroy($id);

        if (is_int($id) && $parentResult) {
            // Удаляем вопросы, которые были связаны с категорией
            $questionsTableName = Question::getDbTableName();
            $sql = "DELETE FROM $questionsTableName WHERE category_id = :id;";
            $statement = self::getDB()->prepare($sql);
            $statement->bindParam('id', $id);
            $result = $statement->execute();
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Возвращает список категорий с количеством вопросов, неотвеченных и опубликованных
     * @return array
     */
    public static function getCategoriesList()
    {
        $tableName = self::$dbTableName;
        $publishedQuestions = Question::QUESTION_STATE_PUBLISHED;
        $waitAnswerQuestions = Question::QUESTION_STATE_WAIT_ANSWER;
        $hiddenQuestions = Question::QUESTION_STATE_HIDDEN;
        $blockedQuestions = Question::QUESTION_STATE_BLOCKED;
        $sql =
            "SELECT cat.id, cat.name, cat.created_at, cat.updated_at, cat.user_id,
                (SELECT count(*) FROM faq_questions WHERE faq_questions.category_id=cat.id) AS all_questions,
                (SELECT count(*) FROM faq_questions WHERE faq_questions.category_id=cat.id AND faq_questions.state = :published_questions) AS published_questions,
                (SELECT count(*) FROM faq_questions WHERE faq_questions.category_id=cat.id AND faq_questions.state = :wait_answer_questions) AS wait_answer_questions,
                (SELECT count(*) FROM faq_questions WHERE faq_questions.category_id=cat.id AND faq_questions.state = :hidden_questions) AS hidden_questions,
                (SELECT count(*) FROM faq_questions WHERE faq_questions.category_id=cat.id AND faq_questions.state = :blocked_questions) AS blocked_questions
            FROM $tableName AS cat
            GROUP BY cat.id
            ORDER BY name;";

        $statement = self::getDB()->prepare($sql);
        $statement->bindParam('published_questions', $publishedQuestions);
        $statement->bindParam('wait_answer_questions', $waitAnswerQuestions);
        $statement->bindParam('hidden_questions', $hiddenQuestions);
        $statement->bindParam('blocked_questions', $blockedQuestions);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Добавляет/изменяет категорию в БД
     * @param $operation
     * @param $name
     * @param $id
     * @return bool
     */
    public function setItem($operation, $name, $id = null)
    {
        $tableName = static::$dbTableName;
        switch ($operation) {
            case 'update';
                $operationHint = 'updated';
                if (!self::find($id)) {
                    $message = new Message("Category was not $operationHint - not found",
                        Message::WARNING, 404);
                    $message->save();
                    return false;
                }
                $sql = "UPDATE $tableName 
                        SET name = :name, user_id = :userId, updated_at = NOW() 
                        WHERE id = :id LIMIT 1";
                break;
            default:
                $operationHint = 'added';
                if (self::getItem($name)) {
                    $message = new Message("Category was not $operationHint -  there is already such with this name",
                        Message::WARNING, 400);
                    $message->save();
                    return false;
                }
                $sql = "INSERT INTO $tableName (name, user_id, created_at, updated_at) 
                        VALUES (:name, :userId, NOW(), NOW())";
                break;
        }

        $statement = self::getDB()->prepare($sql);
        $statement->bindParam('name', $name);
        $statement->bindParam('userId', $this->getCurrentUser('id'));

        if (!empty($id) && $operation === 'update') {
            $statement->bindParam('id', $id);
        }
        $result = $statement->execute();

        if ($result) {
            $message = new Message(
                "Category successfully $operationHint",
                Message::SUCCESS,
                200
            );
        } else {
            $message = new Message(
                "Category was not $operationHint",
                Message::WARNING,
                400
            );
        }

        $message->save();
        return $result;
    }
}