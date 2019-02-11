<?php

class UsersController extends BaseController
{
    protected $modelName = 'User';
    protected $loginTemplate = 'login.twig';
    protected $template = 'users.twig';


    /**
     * Изменение/добавление пользователя
     * @param $params
     */
    public function update($params)
    {
        $this->checkLogin(); // если не залогинен - переадресуем на страницу входа
        $thisModel = $this->getThisModel();
        $operation = !empty($params['id']) ? 'update' : 'create';
        $login = Request::get('name');
        $password = Request::get('password');
        $result = $thisModel->setUser($operation, $login, $password, $params['id']);

        $userName = $this->getThisModel()->getUserName();
        if ($result) {
            if (!empty($params['id'])) {
                $userId = $params['id'];
                $logMsg = "$userName updated \"$login\" ($userId)";
            } else {
                $logMsg = "$userName added \"$login\"";
            }
            Logger::getLogger('actions')->log($logMsg);
        }
        $this->index($params);
    }

    /**
     * Возвращает текущую модель
     * @return User
     */
    protected function getThisModel()
    {
        return $this->model;
    }

    /**
     * Отвечает за отображение
     * @param $params array
     * @param $items array
     */
    public function index($params, $items = [])
    {
        $this->checkLogin(); // если не залогинен - переадресуем на страницу входа

        $thisModel = $this->getThisModel();
        $params = array_merge($params, $this->getNeedParams()); // добавляем требуемые для меню параметры
        $params['user'] = $thisModel->getUserName();
        $params['items'] = count($items) > 0 ? $items : $thisModel::all();
        if (!empty($params['id'])) {
            $params['editItem'] = $thisModel::find($params['id']);
        }
        $params['errors'] = Message::all();
        $this->render($this->template, $params);
    }

    /**
     * Выполняет авторизацию
     * @param $params
     */
    public function postLogin($params)
    {
        if (isPost()) {
            $this->getThisModel()->checkForLogin(getParam('login'), getParam('password'));
            if (!empty(getParam('remember_me'))) {
                Session::Put('authLogin', getParam('login'));
                Session::Put('remember_me', 'checked');
            }
        }

        $this->getLogin($params);
    }

    /**
     * Форма авторизации
     * @param $params
     */
    public function getLogin($params)
    {
        $userName = $this->getThisModel()->getUserName();
        if (!empty($userName)) {
            $params['user'] = $userName;
            Router::redirect('index');
        }
        $params['authLogin'] = Session::Get('authLogin');
        $params['remember_me'] = Session::Get('remember_me');
        $params['errors'] = Message::all();
        $this->render($this->loginTemplate, $params);
    }

    /**
     * Выход из пользователя
     */
    public function getLogout()
    {
        $this->getThisModel()->logout();
    }
}