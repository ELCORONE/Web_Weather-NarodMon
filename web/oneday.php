<?php
$sql_connect_day = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_base) or die("Ошибка " . mysqli_error($sql_connect));

$query_day = "SELECT * FROM $sql_day";
$result_day = $sql_connect_day->query($query_day);

$hpressures = array();
$hours = array();

while($row = mysqli_fetch_array($result_day)) {
	$hours[] = $row['hours'];
	$hpressures[] = $row['pressure'];
	}

$hour = date("H");

if($hour != $hours[23]) {
	for ($i = 1; $i <= 24; $i++){
		if($i == 24) {	// Если последний номер - записывать текущие значения в таблицу
			$hpressures[$i] = $pressure;
			$hours[$i] = $hour;
		}
		else {			// Текущий = следующий
			$hpressures[$i] = next($hpressures);
			$hours[$i] = next($hours);
		}
		// Запись данных в таблицу
		$query_temp_day = "UPDATE $sql_day SET pressure=$hpressures[$i],hours='$hours[$i]' WHERE id=$i";
		$result_temp = $sql_connect_day->query($query_temp_day);
		if (!$query_temp_day) die('updating error'. mysql_error()); // Сообщение об ошибке
	}	
}
mysqli_close($sql_connect_day);	// Закрывание соедининия с MySQL
?>
