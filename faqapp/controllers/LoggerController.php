<?php

class LoggerController extends BaseController
{
    protected $modelName = 'Logger';
    protected $template = 'log.twig';

    public function update($params)
    {
        $this->index($params);
    }

    /**
     * Отвечает за отображение
     * @param $params array
     * @param $items array
     */
    public function index($params, $items = [])
    {
        // если не залогинен - переадресуем на страницу входа
        $this->checkLogin();
        $thisModel = $this->getThisModel();

        // добавляем требуемые для меню параметры
        $params = array_merge($params, $this->getNeedParams());
        $params['user'] = $thisModel->getUserName();
        $params['logs'] = $thisModel->getLogContents('actions');
        $params['errors'] = Message::all();
        $this->render($this->template, $params);
    }

    /**
     * Возвращает текущую модель
     * @return Logger
     */
    protected function getThisModel()
    {
        return $this->model;
    }
}