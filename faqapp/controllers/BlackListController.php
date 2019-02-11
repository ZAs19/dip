<?php

class BlackListController extends BaseController
{
    protected $modelName = 'BlackList';
    protected $template = 'blacklist.twig';

    /**
     * Обновление списка запрещенных ключевых слов
     * @param $params
     */
    public function update($params)
    {
        $this->checkLogin();
        $list = explode("\n", getParam('list'));
        foreach (array_keys($list) as $key) {
            $list[$key] = trim($list[$key]);
        }
        $userName = $this->getThisModel()->getUserName();
        $result = $this->getThisModel()->setItems($list);

        if ($result) {
            $message = new Message("Blacklist was successfully updated", Message::SUCCESS, 200);
            $logMsg = "$userName updated blacklist";
            Logger::getLogger('actions')->log($logMsg);
        } else {
            $message = new Message("Blacklist has not been updated", Message::WARNING, 400);
        }
        $message->save();

        $this->index($params);
    }

    /**
     * @return BlackList
     */
    protected function getThisModel()
    {
        return $this->model;

    }

    /**
     * @param $params
     * @param $items array
     */
    public function index($params, $items = [])
    {
        $this->checkLogin();
        $thisModel = $this->getThisModel();
        $userName = $thisModel->getUserName();
        $params = array_merge($params, $this->getNeedParams()); // добавляем требуемые для меню параметры
        $params['errors'] = Message::all();
        $params['user'] = $userName;
        $params['items'] = implode(PHP_EOL, $thisModel->getBlackList());

        $this->render($this->template, $params);
    }
}