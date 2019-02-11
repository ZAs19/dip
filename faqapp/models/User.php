<?php

class User extends BaseModel
{
    protected static $dbTableName = 'faq_users';

    /**
     * Добавляет/изменяет пользователя в БД (если пользователя с таким именем в базе нет)
     * @param $operation
     * @param $login
     * @param $password
     * @param $id
     * @param $role
     * @return bool
     */
    public function setUser($operation, $login, $password, $id = null, $role = 'admin')
    {
        $operationHint = self::find($id) ? 'updated' : 'added';
        switch ($operation) {
            case 'update';
                if (!self::find($id)) {
                    $message = new Message(
                        'Admin was not updated - no such admin',
                        Message::WARNING,
                        404
                    );
                    $message->save();
                    return false;
                }
                $sql = "UPDATE faq_users 
                        SET name = :login, password = :password, role = :role, updated_at = NOW() 
                        WHERE id = :id LIMIT 1";
                break;
            default:
                if (self::getItem($login)) {
                    $message = new Message(
                        'Admin was not added - this login already exists',
                        Message::WARNING,
                        400
                    );
                    $message->save();
                    return false;
                }
                $sql = "INSERT INTO faq_users (name, password, role, created_at, updated_at) 
                        VALUES (:login, :password, :role, NOW(), NOW())";
                break;
        }
        $statement = self::getDB()->prepare($sql);
        $statement->bindParam('login', $login);
        $statement->bindParam('password', $this->getHash($password));
        $statement->bindParam('role', $role);

        if (!empty($id) && $operation === 'update') {
            $statement->bindParam('id', $id);
        }
        $result = $statement->execute();

        if ($result) {
            $message = new Message(
                "Admin successfully $operationHint",
                Message::SUCCESS,
                200
            );
        } else {
            $message = new Message(
                "Admin was not $operationHint",
                Message::WARNING,
                400
            );
        }

        $message->save();
        return $result;
    }

    /**
     * Реализует механизм проверок при авторизации
     * @param $login
     * @param $password
     * @return bool
     */
    public function checkForLogin($login, $password)
    {
        if (!$this->login($login, $password)) {
            $message = new Message(
                'Authorization failed: no user found, wrong username or password',
                Message::WARNING,
                400
            );
            $message->save();
            return false;
        }
        return true;
    }

    /**
     * Реализует механизм авторизации
     * @param $login
     * @param $password
     * @return bool
     */
    protected function login($login, $password)
    {
        $user = !empty($login) && !empty($password) ? self::getItem($login) : null;
        /* Ищем пользователя по логину */
        if ($user !== null && $user['password'] === $this->getHash($password)) {
            Session::Put('user', $user);
            $this->user = $user;
            Session::Put('user_id', $this->user['id']);
            return true;
        }
        return false;
    }

    /**
     * Уничтожает сессию и переадресует на страницу входа
     */
    public function logout()
    {
        session_destroy();
        Router::redirect('login');
    }
}