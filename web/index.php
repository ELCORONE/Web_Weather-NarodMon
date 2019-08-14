<?php
require_once 'config.php';

$sql_connect = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_base) or die("Ошибка " . mysqli_error($sql_connect));
$sql_connect->set_charset('utf8');

require_once 'forecast.php';

$id = array();
$temp = array();
$hemi = array();
$pressure = array();

$query_table = "SELECT * FROM $sql_tabl";
$result_table = $sql_connect->query($query_table); 

while($row = mysqli_fetch_array($result_table)){
	$id[] = $row['id'];
	$temp[] = $row['temp'];
	$hemi[] = $row['hemi'];
	$pressure[] = round($row['pressure']* 0.00750062,2);
}
?>
<html>
<head>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript">
		google.charts.load('current', {'packages':['corechart']});
		google.charts.setOnLoadCallback(drawChart);

		function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Последний час', 'Давление'],
				<?php for($i = 0; $i < 9;$i++)
						{
						echo "['$id[$i]', $pressure[$i]],";
						}
						echo "['10', $pressure[9]]";
				?>
		]);
		var options = {
			title: 'Давление за час',
			curveType: 'function',
			legend: { position: 'bottom' }
		};
		var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
		chart.draw(data, options);
		}
    </script>
	<style>
		body {background:#ddf1ff url(img/bg.jpg);background-size: cover;}
		table{width:500px; margin:0 auto;border-collapse:collapse}
		td {text-align:center;font-size:18px}
		tbody tr:nth-child(odd){background-color: #C9E4F6;}
		tbody tr:nth-child(even){background-color: #B4DAF2;}
	</style>
</head>
<body>
<div style="margin:0 auto;width:500px;text-align:justify">
<table><tbody>
<tr><td>ID</td><td>Температура, °С</td><td>Влажность, %</td><td>Давление, мм.рт.ст</td></tr>
<?php
for ($i = 0; $i < 10; $i++){
	echo "<tr><td>" . $id[$i] . "</td><td>" . $temp[$i] . "</td><td>" . $hemi[$i] . "</td><td>" . $pressure[$i] . "</td></tr>"; 
	}
echo "</tbody></table>";
mysqli_close($sql_connect);
$weather_margin = $dpressure+248;

?><br>
Шкала показывает ближайшее прогнозирование погоды. <br>Данные колеблются от -50 до +50, то всё будет стабильно.<br>
Если шкала ушла от центра дальше -50, погода ухудшится. <br>
Если шкала ушла от центра дальше -200, то скоро будет аппокалипсис.
Если шкала ушла от центра дальше +50, то улучшиться.<br>
	<div style="width:500px; margin:0 auto;height:30px;background: #88ff88;margin-top:10px;">
		<div style="width:4px;height:40px;z-index:9999;margin-top:-5px;margin-left:<?php echo $weather_margin; ?>px; position:absolute; background: #5ece5e;font-size:13px;" title="">
			<?php echo $dpressure ?>
		</div>
	</div>
	<div id="curve_chart" style="width: 500px; height: 300px;margin-top:50px;"></div>
</div>
</body>
</html>
