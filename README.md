Инструкция по установке и первому запуску:

Установить composer (https://getcomposer.org).
Клонировать репозиторий проекта.
Установить необходимые пакеты (composer install).
Восстановить из дампа faq.sql содержимое базы данных.
В файле config.php отредактировать данные для подключения к базе данных.
Настроить веб сервер - обращение осуществляется к файлу index.php в корне проекта:
.../index.php - список вопросов и ответов (клиентская часть).
.../index.php?/login/ или .../?/login/ - форма авторизации для входа в интерфейс администратора.
Ссылки на проект:
гость http://university.netology.ru/user_data/asavin/diploma/index.php
админ http://university.netology.ru/user_data/asavin/diploma/index.php?/login/
