<?php

require_once 'config.php';						// Конфигурация подключения к MySQL
date_default_timezone_set('Asia/Vladivostok');	// Временная зона
$time = date("H:i");							// Время

// Если пришли значения с ESP8266
if(!empty($_POST['secretkey']) && $_POST['secretkey'] == "ButterFly140")
    {
	//Подключение к базе с показаниями за час
	$sql_connect = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_base) or die("Ошибка " . mysqli_error($sql_connect));
    $mac_address = $_POST['mac_address'];	// Получение MAC-адреса
    $temperature = $_POST['temperature'];	// Получение температуры
    $humidity = $_POST['humidity'];			// Получение влажности
    $pressure = $_POST['pressure'];			// Получение давления
	$altitude = $_POST['altitude'];
		
	require_once 'onehour.php';		// Работа с таблицей часа
	require_once 'oneday.php';		// Работа с таблицей дня
	require_once 'forecast.php';	// Прогноз погоды за текущий час
		
	// Лог файл
	$data = "$time / ".$temperature." / ".$humidity." / ".$pressure." / ".$dpressure."\n";
	$textfile = fopen("log.txt", 'a+') or die("не удалось создать файл");
	fwrite($textfile, $data);
	fclose($textfile);
	
	//Отправка данных на НародМониторинг
	/*
	$datanarod = "#"$mac_address."#ELTempo".;
	$fp = @fsockopen("tcp://narodmon.ru", 8283, $errno, $errstr);
	if(!$fp) exit("ERROR(".$errno."): ".$errstr);
	fwrite($fp, $data);
	fclose($fp);*/
	mysqli_close($sql_connect);	// Закрывание соедининия с MySQL
}
else {
	echo "<center><a href=\"http://milky.space\">Access denied</a></center><br>";
}
?>
