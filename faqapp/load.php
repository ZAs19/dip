<?php

session_start();

require_once 'core/functions.php';
spl_autoload_register('autoloader');

// Установка пути к папке с лог-файлом
Logger::$PATH = PATH_TO_LOG;
$logger = new Logger('actions');

Router::$base_route = 'error';

//Регистрация маршрутов
Router::get('/', 'QuestionsController@index', 'index');
Router::post('/', 'QuestionsController@store', 'index_store');

Router::get('/login/', 'UsersController@getLogin', 'login');
Router::post('/login/', 'UsersController@postLogin');
Router::get('/logout/', 'UsersController@getLogout', 'logout');

Router::resource('users', 'UsersController');
Router::resource('questions', 'QuestionsController');
Router::resource('categories', 'CategoriesController');
Router::get("/questions/state/(\d+)", "QuestionsController@index", "quest_by_state", ['state' => 1]);
Router::get("/questions/category/(\d+)", "QuestionsController@index", "quest_by_category", ['category' => 1]);

Router::get('/log/', 'LoggerController@index', 'logger');
Router::get('/blacklist/', 'BlackListController@index', 'blacklist');
Router::post('/blacklist/', 'BlackListController@store', 'blacklist_store');
Router::get('/error/', 'QuestionsController@index', 'error');

$uriQuery = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$uri = is_null($uriQuery) ? '/' : $uriQuery;

try {
    Router::run($uri);
    Session::Put('lastRouteName', Router::currentRouteName());
} catch (Exception $e) {
    Message::setCriticalErrorAndRedirect($e->getMessage(), $e->getCode());
}