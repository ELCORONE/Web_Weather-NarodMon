<?php
// Запрос всех записей таблицы
$query_full = "SELECT * FROM $sql_tabl";
$result_full = $sql_connect->query($query_full);

//Переменные ввиде массива
$temps = array();
$hemis = array();
$pressurs = array();

//Получение всех значений таблицы
while($row = mysqli_fetch_array($result_full)) {
	$temps[] = $row['temp'];
	$hemis[] = $row['hemi'];
	$pressures[] = $row['pressure'];
	}
// Обновление данных и запись в таблицу
for ($i = 1; $i <= 10; $i++){
	if($i == 10) {	// Если последний номер - записывать текущие значения в таблицу
		$temps[$i] = $temperature;
		$hemis[$i] = $humidity;
		$pressures[$i] = $pressure;
	}
	else {			// Текущий = следующий
		$temps[$i] = next($temps);
		$hemis[$i] = next($hemis);
		$pressures[$i] = next($pressures);
	}
	// Запись данных в таблицу
	$query_temp = "UPDATE $sql_tabl SET temp=$temps[$i],hemi=$hemis[$i],pressure=$pressures[$i] WHERE id=$i";
	$result_temp = $sql_connect->query($query_temp);
	if (!$result_temp) die('updating error'. mysql_error()); // Сообщение об ошибке
	}
?>
