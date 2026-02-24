<?php
// Настройка сайта
date_default_timezone_set('Asia/Vladivostok');	// Временная зона
define('ROOT', $_SERVER['DOCUMENT_ROOT']);		// Корневая папка
// Конфигурация основая - база данных
define('SQL_HOST', 'localhost');
define('SQL_USER', 'root');
define('SQL_PASS', 'password');       // Пароль от базы данных
define('SQL_BASE', 'tempbase');       // Имя базы данных с погодой

?>
