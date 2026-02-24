<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Погодная станция</title>
    <link href="/files/style.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="HandheldFriendly" content="true">
    <!-- Подключаем Chart.js -->
    <script src="/files/chart.js"></script>
    <style>
        
    </style>
</head>
<body>
    
    <div class="weather-timeout"><span id="timeout">Загрузка данных...</span><br/>Текущие показания:</div>
    
    <div class="container disconn" id="weather-container">
        <div class="container-block">
            <div id="tempColor" class="weather-line"></div>
            <p>Температура</p>
            <span id="temp-value">--<i>°C</i></span>
            <div class="weather-info">
                <div id="temp-difference">--</div>
                <div id="temp-max">max: --</div>
                <div id="temp-min">min: --</div>
            </div>
        </div>
        <div class="container-block">
            <div id="humidity-line" class="weather-line"></div>
            <p>Влажность</p>
            <span id="humidity-value">--<i>%</i></span>
            <div class="weather-info">
                <div id="humidity-difference">--</div>
                <div id="humidity-max">max: --</div>
                <div id="humidity-min">min: --</div>
            </div>
        </div>
        <div class="container-block">
            <div id="pressure-line" class="weather-line"></div>
            <p>Давление</p>
            <span id="pressure-value" title="--">--<i>мм. рт. ст.</i></span>
            <div class="weather-info">
                <div id="pressure-difference">--</div>
                <div id="pressure-max">max: --</div>
                <div id="pressure-min">min: --</div>
            </div>
        </div>
    </div>

    <!-- Контейнер для графиков -->
    <div class="charts-container" id="charts-container" style="display: none;">
        <div class="chart-box">
            <div class="chart-title">📈 Температура</div>
            <canvas id="tempChart"></canvas>
        </div>
        <div class="chart-box">
            <div class="chart-title">💧 Влажность</div>
            <canvas id="humidityChart"></canvas>
        </div>
        <div class="chart-box">
            <div class="chart-title">☁️ Давление</div>
            <canvas id="pressureChart"></canvas>
        </div>
    </div>

    <!-- Кнопки переключения периодов -->
    <div class="chart-controls">
        <button class="chart-btn active" data-period="hour">За час</button>
        <button class="chart-btn" data-period="day">За сутки</button>
    </div>
    <div class="footer">Powered by: ESP8266 + Apache + MySQL + AJAX + Chart.js</div>

   <script src="resource/script.js"></script>
</body>
</html>
