<?php

abstract class BaseController
{
    protected $model = null;
    protected $modelName = 'BaseModel';
    protected $template;
    protected $errorTemplate = 'error.twig';

    /**
     * BaseController constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $modelFile = 'faqapp/models/' . $this->modelName . '.php';
        if (is_file($modelFile)) {
            if (!class_exists($this->modelName)) {
                include $modelFile;
            }
            $this->model = new $this->modelName();
        } else {
            throw new Exception("Model $modelFile not found");
        }
    }

    /**
     * Обработка POST запросов
     * @param $params
     */
    public function store($params)
    {
        if (Router::currentRouteName() !== 'index_store') {
            // незалогиненному пользователю разрешен только один маршрут - index_store
            $this->checkLogin();
        }

        /* если была нажата кнопка Добавить */
        if (Request::has('add')) {
            $this->update($params);
            die();
        }

        $this->index($params);
    }

    public function checkLogin()
    {
        if (empty($this->getThisModel()->getUserName())) {
            // если не залогинен
            Router::redirect('login');
        }
    }

    abstract protected function getThisModel();

    abstract public function update($params);

    abstract public function index($params, $items = []);

    /**
     * Отображения форму создания сущности
     * @param $params
     */
    public function create($params)
    {
        $this->checkLogin(); // если не залогинен - переадресуем на страницу входа
        $params['action'] = 'create';
        $this->index($params);
    }

    /**
     * Удаляет сущность
     * @param $params
     */
    public function destroy($params)
    {
        $this->checkLogin(); // если не залогинен - переадресуем на страницу входа
        $thisModelClass = $this->modelName;
        if (count($thisModelClass::all()) > 1) {
            $result = $thisModelClass::destroy($params['id']);
        } else {
            $result = false;
            $message = new Message('Delete failed: you can not delete the last item',
                Message::WARNING, 400);
        }
        $userName = $this->getThisModel()->getUserName();
        if ($result) {
            $message = new Message('Deleting successfully', Message::SUCCESS, 200);
            $itemId = $params['id'];
            switch ($this->modelName) {
                case 'Category':
                    $itemName = 'category';
                    break;
                case 'User':
                    $itemName = 'admin';
                    break;
                case 'Question':
                    $itemName = 'question';
                    break;
                default:
                    $itemName = 'element';
                    break;
            }

            $logMsg = "$userName deleted $itemName ($itemId)";
            Logger::getLogger('actions')->log($logMsg);
        } else {
            if (empty($message)) {
                $message = new Message('Delete failed', Message::WARNING, 400);
            }
        }
        $message->save();
        $this->index($params);
    }

    /**
     * Вывод формы с полями для изменения сущности
     * @param $params
     */
    public function edit($params)
    {
        $this->checkLogin(); // если не залогинен - переадресуем на страницу входа
        $params['action'] = 'edit';
        $this->index($params);
    }

    /**
     * Выводит сущность по id
     * @param $params
     */
    public function show($params)
    {
        $this->checkLogin(); // если не залогинен - переадресуем на страницу входа
        $thisModelClass = $this->modelName;
        $items = $thisModelClass::find($params['id']);
        $this->index($params, $items);
    }

    /**
     * Возвращает массив со списком состояний, тем и статистикой
     * Это необходимо для вывода меню
     * @return array
     */
    protected function getNeedParams()
    {
        $params['categories'] = Category::getCategoriesList();
        $params['states'] = Question::getQuestionStateList();

        foreach ($params['categories'] as $category) {
            $params['num_question_categories'][$category['id']] = array_sum([(int)$category['published_questions'],
                (int)$category['hidden_questions'], (int)$category['wait_answer_questions'],
                (int)$category['blocked_questions']]);
        }

        $params['num_questions'] = count(Question::all());

        foreach ($params['states'] as $key => $state) {
            foreach ($params['categories'] as $category) {
                switch ($state) {
                    case Question::QUESTION_STATE_PUBLISHED;
                        $params['num_question_states'][$key] = (int)$params['num_question_states'][$key] + (int)$category['published_questions'];
                        break;
                    case Question::QUESTION_STATE_HIDDEN;
                        $params['num_question_states'][$key] = (int)$params['num_question_states'][$key] + (int)$category['hidden_questions'];
                        break;
                    case Question::QUESTION_STATE_WAIT_ANSWER;
                        $params['num_question_states'][$key] = (int)$params['num_question_states'][$key] + (int)$category['wait_answer_questions'];
                        break;
                    case Question::QUESTION_STATE_BLOCKED;
                        $params['num_question_states'][$key] = (int)$params['num_question_states'][$key] + (int)$category['blocked_questions'];
                        break;
                }
            }
        }

        return $params;
    }

    /**
     * Отображаем шаблон
     * @param $template
     * @param array $params
     */
    protected function render($template, $params = [])
    {
        // Где лежат шаблоны
        $loader = new Twig_Loader_Filesystem('faqapp/views/');

        // Где будут хранится файлы кэша (php файлы)
        $twig = new Twig_Environment($loader, array(
            'debug' => true,
            'cache' => 'faqapp/storage/tmp/twig_cache',
            'auto_reload' => true,
        ));

        $twig->addFunction('staticCall', new Twig_Function_Function('staticCall'));
        $twig->addExtension(new Twig_Extension_Debug());

        try {
            echo $twig->render($template, $params);
            die;
        } catch (Exception $e) {
            Message::setCriticalErrorAndRedirect($e->getMessage(), $e->getCode());
        }
    }
}