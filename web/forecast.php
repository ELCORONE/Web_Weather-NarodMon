<?php

//Фунция преобразования одного диапазона в другой
function map($value, $fromLow, $fromHigh, $toLow, $toHigh) {
    $fromRange = $fromHigh - $fromLow;
    $toRange = $toHigh - $toLow;
    $scaleFactor = $toRange / $fromRange;
    $tmpValue = $value - $fromLow;
    $tmpValue *= $scaleFactor;
    return $tmpValue + $toLow;
}

// Выборка данных из MySQL
$query_weather = "SELECT * FROM $sql_tabl";
$result_weather = $sql_connect->query($query_weather);
$pressure_array = array();
$time_array = array();

while($weather_row = mysqli_fetch_array($result_weather)) {
	$pressure_array[] = $weather_row['pressure'];
	$time_array[] = $weather_row['id'];
}

// Обнуляем показания для формулы
$sumX = 0;
$sumY = 0;
$sumX2 = 0;
$sumXY = 0;

for ($i = 0; $i < 10; $i++) {	// Расчет переменных для формулы
	$sumX += $time_array[$i];
	$sumY += $pressure_array[$i];
	$sumX2 += $time_array[$i] * $time_array[$i];
	$sumXY += $time_array[$i] * $pressure_array[$i];
}
/// Расчет формулы
$factor = 0;
$factor = 10 * $sumXY; // Расчёт коэффициента наклона приямой
$factor = $factor - $sumX * $sumY;
$factor = $factor / (10 * $sumX2 - $sumX * $sumX);
$dpressure = $factor * 10;  // Расчёт изменения давления
$dpressure = round($dpressure); // Округление показания до двух знаков после запятой
$mapdelta = map($dpressure, -250, 250, 100, -100); // Преобразование диапазона
?>
