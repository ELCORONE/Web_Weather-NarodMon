<html>
<head>
<style>
	.body {background:#dadada}
	table{width:500px; margin:0 auto;}
	td {text-align:center;font-size:18px}
	tbody tr:nth-child(odd){background-color: #C9E4F6;}
	tbody tr:nth-child(even){background-color: #B4DAF2;}
</style>
</head>
<?php
require_once 'config.php';

$sql_connect = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_base) or die("Ошибка " . mysqli_error($sql_connect));
$sql_connect->set_charset('utf8');

require_once 'forecast.php';

$query_table = "SELECT * FROM $sql_tabl";
$result_table = $sql_connect->query($query_table);

echo "<table><tbody>";

while($row = mysqli_fetch_array($result_table)){
	echo "<tr><td>" . $row['id'] . "</td><td>" . $row['temp'] . "</td><td>" . $row['hemi'] . "</td><td>" . $row['pressure']* 0.00750062 . "</td></tr>"; 
	}
	
echo "</tbody></table>";

echo "<div style=\"width:486px;margin:0 auto;padding:5px;text-align:center\">Вероятность дожня: ".$mapdelta."%</div>";

mysqli_close($sql_connect);
?>
</html>
