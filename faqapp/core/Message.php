<?php

class Message
{
    const WARNING = 'alert-warning';    // Предупреждающее сообщение
    const DANGER = 'alert-danger';      // Сообщение об ошибке
    const SUCCESS = 'alert-success';    // Сообщение об успешном выполнении действия или подтверждающее сообщение
    const INFO = 'alert-info';          // Информационные сообщения
    protected $message;
    protected $type;
    protected $code;


    public function __construct($message, $type, $errorCode, $save = true)
    {
        $type = $this->checkType($type) ? $type : self::INFO;
        $this->message = $message;
        $this->type = $type;
        $this->code = $errorCode;
    }

    /**
     * Проверяет правильный ли тип
     * @param $type
     * @return bool
     */
    protected function checkType($type)
    {
        return in_array($type, [self::WARNING, self::DANGER, self::SUCCESS, self::INFO]);
    }

    /**
     * Возвращает список ошибок в виде объектов
     * @return Message[]
     */
    public static function all()
    {
        $errorsList = Session::Flash('errors');
        if (is_array($errorsList)) {
            foreach ($errorsList as $error) {
                $errors[] = new Message($error['message'], $error['type'], $error['code'], false);
            }
        }

        return isset($errors) ? $errors : [];
    }

    /**
     * Создает критическую ошибку и выполняет редирект
     * @param $message
     * @param $code
     * @param string $type
     */
    public static function setCriticalErrorAndRedirect($message, $code, $type = self::DANGER)
    {
        $message = new Message($message, $type, $code);
        $message->save();
        Router::redirect(Router::$base_route);
    }

    /**
     * Сохраняет ошибку в сессию
     */
    public function save()
    {
        Session::Add(
            'errors',
            [
                'message' => $this->getMessage(),
                'type' => $this->getType(),
                'code' => $this->getCode()
            ]);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }
}