<?php

class Router
{
    public static $base_route = 'index';
    private static $dirController = 'faqapp/controllers/';
    private static $urls = [];
    private static $curRouteName;
    private static $curRouteAction;
    private static $curRouteUrl = '';

    /**
     * Добавление роутеров с методом GET
     * @param $url
     * @param $controllerAndAction //пример: QuestionsController@index
     * @param string $routeName
     * @param array $params
     */
    public static function get($url, $controllerAndAction, $routeName = '', $params = [])
    {
        self::add('GET', $url, $controllerAndAction, $routeName, $params);
    }

    /**
     * Добавление роутеров в массив
     * @param $method
     * @param $url
     * @param $controllerAndAction //пример: BookController@list
     * @param $routeName
     * @param $params
     */
    public static function add($method, $url, $controllerAndAction, $routeName, $params)
    {
        list($controller, $action) = explode('@', $controllerAndAction);

        self::$urls[$method][$url] = [
            'controller' => $controller,
            'action' => $action,
            'routeName' => $routeName,
            'params' => $params
        ];
    }

    /**
     * Добавление ресурса
     * ------
     * Действия, обрабатываемые ресурс-контроллером:
     * VERB         ПУТЬ                 ДЕЙСТВИЕ        ИМЯ МАРШРУТА
     * ---------------------------------------------------------------
     * GET          /photo                index         photo.index
     * GET          /photo/create         create        photo.create
     * POST         /photo                store         photo.store
     * GET          /photo/{photo}        show          photo.show
     * GET          /photo/{photo}/edit   edit          photo.edit
     * PUT          /photo/{photo}        update        photo.update
     * DELETE       /photo/{photo}        destroy       photo.destroy
     * ---------------------------------------------------------------
     * Пример: Route::resource('photo', 'PhotoController');
     * @param $resource
     * @param $controller //пример: PhotoController
     */
    public static function resource($resource, $controller)
    {
        //route('contacts.edit', ['id' => $contact->id])
        self::add('GET', "/$resource", "$controller@index", "$resource.index", []);
        self::add('GET', "/$resource/create", "$controller@create", "$resource.create", []);
        self::add('POST', "/$resource", "$controller@store", "$resource.store", []);
        self::add('GET', "/$resource/(\d+)", "$controller@show", "$resource.show", ['id' => 1]);
        self::add('GET', "/$resource/(\d+)/edit", "$controller@edit", "$resource.edit", ['id' => 1]);
        self::add('PUT', "/$resource/(\d+)", "$controller@update", "$resource.update", ['id' => 1]);
        self::add('DELETE', "/$resource/(\d+)", "$controller@destroy", "$resource.destroy", ['id' => 1]);
    }

    /**
     * Добавление роутеров с методом POST
     * @param $url
     * @param $controllerAndAction //пример: BookController@postUpdate
     * @param string $routeName
     * @param array $params
     */
    public static function post($url, $controllerAndAction, $routeName = '', $params = [])
    {
        self::add('POST', $url, $controllerAndAction, $routeName, $params);
    }

    /**
     * Возвращает текущее название маршрута
     * @return string
     */
    public static function currentRouteName()
    {
        return !empty(self::$curRouteName) ? self::$curRouteName : '';
    }

    /**
     * Возвращает текущее название действия
     * @return string
     */
    public static function currentRouteAction()
    {
        return !empty(self::$curRouteAction) ? self::$curRouteAction : '';
    }

    /**
     * Возвращает текущий юрл
     * @return string
     */
    public static function currentRouteUrl()
    {
        return !empty(self::$curRouteUrl) ? self::$curRouteUrl : '';
    }

    /**
     * Отправляет переадресацию на указанную страницу
     * @param $action
     */
    public static function redirect($action)
    {
        if (!headers_sent()) {
            header('Location: ' . self::route($action));
        }
        die;
    }

    /**
     * Возвращает ссылку по имени маршрута
     * @param $routeName // пример: photo.index для ресурсов или index
     * @param array
     * @return string
     */
    public static function route($routeName, $params = [])
    {
        if (isset(self::$urls)) {
            foreach (self::$urls as $urlMethod) {
                foreach ($urlMethod as $url => $urlData) {
                    if ($urlData['routeName'] === $routeName) {
                        if (count($params) > 0) {
                            foreach ($params as $key => $param) {
                                $url = str_replace('(\d+)', $param, $url);
                            }
                        }
                        return '?' . $url;
                    }
                }
            }
        }
        return '';
    }

    /**
     * Подключение контроллеров
     * @param $currentUrl //текущий урл
     * @throws Exception
     */
    public static function run($currentUrl)
    {
        $routeFound = false;
        $requestMethod = Request::has('_method') ? Request::get('_method') : $_SERVER['REQUEST_METHOD'];
        if (isset(self::$urls[$requestMethod])) {
            foreach (self::$urls[$requestMethod] as $url => $urlData) {
                if (preg_match('(^' . $url . '$)', $currentUrl, $matchList)) {
                    $params = [];
                    foreach ($urlData['params'] as $param => $i) {
                        $params[$param] = $matchList[$i];
                    }

                    $controllerName = $urlData['controller'];
                    $action = $urlData['action'];
                    self::$curRouteName = $urlData['routeName'];
                    self::$curRouteAction = $urlData['action'];
                    self::$curRouteUrl = $currentUrl;
                    $controllerFile = self::$dirController . $controllerName . '.php';

                    if (is_file($controllerFile)) {
                        include $controllerFile;
                    } else {
                        throw new Exception("Controller file $controllerFile not found", '404');
                    }

                    $controller = new $urlData['controller']();
                    if (method_exists($controller, $action)) {
                        $controller->$action($params);
                    } else {
                        throw new Exception("Method $action not found in the controller $controllerName", '404');
                    }
                    $routeFound = true;
                }
            }
        }
        if ($routeFound === false) {
            throw new Exception("Route $currentUrl not found, method: $requestMethod", '404');
        }
    }
}