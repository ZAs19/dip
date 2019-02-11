<?php

class Question extends BaseModel
{
    const QUESTION_STATE_PUBLISHED = 'published';
    const QUESTION_STATE_HIDDEN = 'hidden';
    const QUESTION_STATE_WAIT_ANSWER = 'waiting';
    const QUESTION_STATE_BLOCKED = 'blocked';
    const QUESTION_STATE_ALL = '%';

    protected static $dbTableName = 'faq_questions';
    protected $requiredFields = ['question', 'author', 'author_email', 'category_id'];

    /**
     * Возвращает список вопросов с категориями из БД
     * @param $state
     * @param null $category_id
     * @return array
     */
    public function getQuestionsList($state, $category_id = null)
    {
        if (in_array($state, self::getQuestionStateList(true))) {
            if (is_null($category_id)) {
                $category_id = '%';
                $sqlCategoryFilter = "category_id LIKE :category_id";
            } else {
                $category_id = (int)$category_id;
                $sqlCategoryFilter = "category_id=:category_id";
            }

            $sqlStateFilter = $state === self::QUESTION_STATE_ALL ? 'state LIKE :state' : 'state=:state';

            $sql = "SELECT questions.id, questions.name, questions.answer, questions.state, questions.created_at, 
                questions.updated_at, questions.category_id, questions.user_id, questions.author, 
                questions.author_email,
                categories.name AS category_name, 
                users.name AS user_login
                FROM faq_questions AS questions
                  LEFT JOIN faq_users AS users ON users.id=IF(questions.user_id IS NULL, '', questions.user_id)
                  JOIN faq_categories AS categories ON categories.id=questions.category_id
                WHERE $sqlStateFilter AND $sqlCategoryFilter 
                ORDER BY category_name, questions.name;";

            $statement = self::getDB()->prepare($sql);
            $statement->bindParam('state', $state);
            $statement->bindParam('category_id', $category_id);
            $statement->execute();
            $unsortedArray = $statement->fetchAll(PDO::FETCH_ASSOC);
            $questions = [];

            foreach ($this->getCategoriesList() as $category) {
                $questions[$category['name']] = [];
            }

            foreach ($unsortedArray as $question) {
                $questions[$question['category_name']][] = $question;
            }

            return $questions;
        }
        return null;
    }

    /**
     * Возвращает список состояний вопросов
     * @param bool $withAll
     * @return array
     */
    public static function getQuestionStateList($withAll = false)
    {
        $states = array(
            self::QUESTION_STATE_PUBLISHED,
            self::QUESTION_STATE_HIDDEN,
            self::QUESTION_STATE_WAIT_ANSWER,
            self::QUESTION_STATE_BLOCKED
        );
        if ($withAll) {
            $states[] = self::QUESTION_STATE_ALL;
        }
        return $states;
    }

    /**
     * Возвращает список категорий из БД
     */
    public function getCategoriesList()
    {
        $sql = "SELECT id, name FROM faq_categories ORDER BY name;";
        $statement = self::getDB()->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Добавляет/изменяет категорию в БД
     * @param $operation
     * @param $params
     * @return bool
     */
    public function setItem($operation, $params)
    {
        $tableName = self::$dbTableName;

        if ($this->validate($params)) {
            switch ($operation) {
                case 'update';
                    $sql = "UPDATE $tableName 
                        SET name = :name, author = :author, author_email = :author_email, updated_at = NOW(), 
                        category_id = :category_id, state = :state, user_id = :user_id, answer = :answer
                        WHERE id = :id LIMIT 1;";
                    break;
                default:
                    $sql = "INSERT INTO $tableName 
                      (name, author, author_email, created_at, updated_at, category_id, state, user_id, answer)
                      VALUES (:name, :author, :author_email, NOW(), NOW(), :category_id, :state, :user_id, :answer);";
                    break;
            }

            $params['state'] = !empty($params['state']) ? $params['state'] : self::QUESTION_STATE_WAIT_ANSWER;
            $statement = self::getDB()->prepare($sql);
            $statement->bindParam('name', $params['question']);
            $statement->bindParam('author', $params['author']);
            $statement->bindParam('author_email', $params['author_email']);
            $statement->bindParam('category_id', $params['category_id']);
            $statement->bindParam('state', $params['state']);
            $statement->bindParam('user_id', $this->getCurrentUser('id'));
            $statement->bindParam('answer', $params['answer']);

            if (!empty($params['id']) && $operation === 'update') {
                $statement->bindParam('id', $params['id']);
            }

            return $statement->execute();
        }
        return false;
    }
}