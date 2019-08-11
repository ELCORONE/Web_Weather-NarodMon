<?php

require_once 'config.php'; // Конфигурация подключения к MySQL

//Подключение к MySQL с именем $sql_host под пользователем $sql_user с паролем $sql_pass и выбором базы $sql_base
$sql_connect = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_base) or die("Ошибка " . mysqli_error($sql_connect));
$sql_connect->set_charset('utf8'); // Установка кодировки utf8


date_default_timezone_set('Asia/Vladivostok');	// Временная зона
$d = date("Y-m-d");								// Дата
$t = date("H:i:s");								// Время

// Если пришли значения с ESP8266
if(!empty($_POST['secretkey']) && $_POST['secretkey'] == "ButterFly140")
    {
    $mac_address = $_POST['mac_address'];	// Получение MAC-адреса
    $temperature = $_POST['temperature'];	// Получение температуры
    $humidity = $_POST['humidity'];			// Получение влажности
    $pressure = $_POST['pressure'];			// Получение давления
	$altitude = $_POST['altitude'];			// Получения какой-то хуйни
	
	require_once 'sqlsend.php'; // Работа с таблицей
	
	require_once 'forecast.php';	// Расчет линии трейдинга по наименьшему углу
	
	// Лог файл
	$data = "$t - ".$temperature." - ".$humidity." - ".$pressure." - ".$dpressure." (".$mapdelta.")\n";
	$textfile = fopen("log.txt", 'a+') or die("не удалось создать файл");
	fwrite($textfile, $data);
	fclose($textfile);
	
	//Отправка данных на НародМониторинг (Далее опишу как сделать)
	/*
	$fp = @fsockopen("tcp://narodmon.ru", 8283, $errno, $errstr);
	if(!$fp) exit("ERROR(".$errno."): ".$errstr);
	fwrite($fp, $data);
	fclose($fp);*/
}
mysqli_close($sql_connect);	// Закрывание соедининия с MySQL
?>
