<?php

class QuestionsController extends BaseController
{
    protected $modelName = 'Question';
    protected $clientTemplate = 'client.twig';
    protected $template = 'questions.twig';

    /**
     * Изменение/добавление вопроса
     * @param $params
     */
    public function update($params)
    {
        if (Router::currentRouteName() !== 'index_store') {
            // незалогиненному пользователю разрешен только один маршрут - index_store
            $this->checkLogin();
        }

        $userName = $this->getThisModel()->getUserName();
        $resultHint = Request::has('add') ? 'added' : 'updated';
        $operation = Request::has('add') ? 'create' : 'update';

        $itemParams['author'] = trim(getParam('author'));
        $itemParams['author_email'] = trim(getParam('author_email'));
        $itemParams['question'] = trim(getParam('question'));
        $itemParams['category_id'] = (int)trim(getParam('category_id'));

        foreach (BlackList::getBlackList() as $blackPhrase) {
            if (is_int(strripos(mb_strtolower($itemParams['question']), mb_strtolower($blackPhrase)))) {
                $itemParams['state'] = Question::QUESTION_STATE_BLOCKED;
            }
        }

        if (!empty($userName)) {
            $itemParams['answer'] = trim(getParam('answer'));
            $itemParams['state'] = !empty(getParam('state')) ? trim(getParam('state')) : $itemParams['state'];
            $itemParams['id'] = (int)trim(getParam('id'));
        }

        $result = $this->getThisModel()->setItem($operation, $itemParams);

        if ($result) {
            $message = new Message("Question successfully $resultHint", Message::SUCCESS, 200);
            $userName = !empty($userName) ? $userName : 'guest ' . $itemParams['author'];
            $categoryId = $itemParams['category_id'];
            $category = Category::find($categoryId)['name'];

            if (!empty($itemParams['id'])) {
                $questionId = $itemParams['id'];
                $logMsg = "$userName updated question ($questionId) from category \"$category\" ($categoryId)";
            } else {
                $logMsg = "$userName added question in category \"$category\" ($categoryId)";
            }
            Logger::getLogger('actions')->log($logMsg);
        } else {
            $message = new Message("Question was not $resultHint", Message::WARNING, 400);
            foreach ($itemParams as $key => $itemParam) {
                $params[$key] = $itemParam;
            }
        }
        $message->save();

        $this->index($params);
    }

    /**
     * @return Question
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
        $thisModel = $this->getThisModel();
        $userName = $thisModel->getUserName();
        $params['errors'] = Message::all();
        $params = array_merge($params, $this->getNeedParams()); // добавляем требуемые для меню параметры
        if (!empty($userName)) {
            $params['user'] = $userName;
        }

        if (Router::currentRouteName() !== 'index_store' and Router::currentRouteName() !== 'index') {
            // незалогиненному пользователю разрешены только два маршрута - index_store и index
            $this->checkLogin();
            $template = $this->template;
            $params['items'] = count($items) > 0 ? $items : $thisModel::all();
            if (!empty($params['id'])) {
                $params['editItem'] = $thisModel::find($params['id']);
            }
            $state = $thisModel::QUESTION_STATE_ALL;
            $categoryId = null;

            if (isset($params['state'])) {
                $stateId = $params['state'];
                $state = !empty($params['states'][$stateId]) ? $params['states'][$stateId] : $thisModel::QUESTION_STATE_ALL;
            } elseif (isset($params['category'])) {
                $categoryId = $params['category'];
            }
            $params['categoriesWithQuestions'] = $thisModel->getQuestionsList($state, $categoryId);
        } else {
            $template = $this->clientTemplate;
            $params['categoriesWithQuestions'] = $thisModel->getQuestionsList($thisModel::QUESTION_STATE_PUBLISHED);
        }

        if (Router::currentRouteName() === 'error') {
            $this->render($this->errorTemplate, $params);
            die();
        }

        $this->render($template, $params);
    }
}