<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Илья Корон - Погодная станция</title>
    <link href="style.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="HandheldFriendly" content="true">
    <!-- Подключаем Chart.js -->
    <script src="chart.js"></script>
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

    <script>
    let lastUpdateTime = 0;
    let tempChart, humidityChart, pressureChart;
    let currentPeriod = 'hour';
    let currentWeatherData = [];
    let currentDayData = [];

    // Функция для создания графиков
    function createCharts(weatherData, period = 'hour') {
        const timeLabels = weatherData.map(item => {
            const date = new Date(item.time * 1000);
            if (period === 'hour') {
                return date.getHours() + ':' + date.getMinutes().toString().padStart(2, '0');
            } else {
                return date.getHours() + ':00';
            }
        });

        // Если графики уже созданы - просто обновляем данные
        if (tempChart && humidityChart && pressureChart) {
            updateChartData(tempChart, timeLabels, weatherData.map(item => item.temp));
            updateChartData(humidityChart, timeLabels, weatherData.map(item => item.hemi));
            updateChartData(pressureChart, timeLabels, weatherData.map(item => item.pressure));
            return;
        }

        // График температуры
        tempChart = new Chart(document.getElementById('tempChart'), {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Температура °C',
                    data: weatherData.map(item => item.temp),
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // График влажности
        humidityChart = new Chart(document.getElementById('humidityChart'), {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Влажность %',
                    data: weatherData.map(item => item.hemi),
                    borderColor: '#4ecdc4',
                    backgroundColor: 'rgba(78, 205, 196, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // График давления
        pressureChart = new Chart(document.getElementById('pressureChart'), {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Давление мм.рт.ст',
                    data: weatherData.map(item => item.pressure),
                    borderColor: '#45b7d1',
                    backgroundColor: 'rgba(69, 183, 209, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Показываем контейнер с графиками
        document.getElementById('charts-container').style.display = 'grid';
    }

    // Функция для обновления данных графика без пересоздания
    function updateChartData(chart, labels, data) {
        chart.data.labels = labels;
        chart.data.datasets[0].data = data;
        chart.update('none');
    }

    // Функция для обновления цветовой индикации температуры
    function changeColorBasedOnTemperature(temperature) {
        const temperatureBlock = document.getElementById('tempColor');
        if (temperature >= -40 && temperature < -20) {
            temperatureBlock.style.backgroundColor = 'blue';
        } else if (temperature >= -20 && temperature < 0) {
            temperatureBlock.style.backgroundColor = 'lightblue';
        } else if (temperature >= 0 && temperature < 20) {
            temperatureBlock.style.backgroundColor = 'yellow';
        } else if (temperature >= 20 && temperature < 30) {
            temperatureBlock.style.backgroundColor = 'orange';
        } else if (temperature >= 30) {
            temperatureBlock.style.backgroundColor = 'red';
        } else {
            temperatureBlock.style.backgroundColor = 'black';
        }
    }

    // Функция для обновления таймера
    function updateTimeout() {
        const nowtime = Math.floor(Date.now() / 1000);
        const timeout = nowtime - lastUpdateTime;
        const minutes = Math.floor(timeout / 60);
        const seconds = timeout % 60;
        
        if (lastUpdateTime > 0) {
            document.getElementById('timeout').innerText = 
                'Последнее обновление: ' + minutes + ' минут ' + seconds + ' секунд назад';
        }
    }


    // Функция для переключения между периодами
    function switchChartPeriod(period) {
        currentPeriod = period;
        if (period === 'hour' && currentWeatherData.length > 0) {
            createCharts(currentWeatherData, 'hour');
        } else if (period === 'day' && currentDayData.length > 0) {
            createCharts(currentDayData, 'day');
        }
    }

    // Функция для загрузки данных через AJAX
    function loadWeatherData() {
        fetch('/api/weather.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сети');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateWeatherDisplay(data);
                    lastUpdateTime = data.currentReadings.time;
                    document.getElementById('weather-container').classList.remove('disconn');
                    
                    // Сохраняем данные для переключения периодов
                    currentWeatherData = data.weatherData || [];
                    currentDayData = data.weatherDayData || [];
                    
                    // Создаем графики если есть данные
                    if (currentWeatherData.length > 0) {
                        createCharts(currentWeatherData, currentPeriod);
                    }
                    
                    // Обновляем суточную статистику

                } else {
                    console.error('Ошибка получения данных:', data.error);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                document.getElementById('weather-container').classList.add('disconn');
            });
    }

    // Функция для обновления отображения данных
    function updateWeatherDisplay(data) {
        // Температура
        document.getElementById('temp-value').innerHTML = data.currentReadings.temp + '<i>°C</i>';
        document.getElementById('temp-difference').textContent = data.differences.temp || '--';
        document.getElementById('temp-max').textContent = 'max: ' + data.stats.temp.max;
        document.getElementById('temp-min').textContent = 'min: ' + data.stats.temp.min;
        changeColorBasedOnTemperature(data.currentReadings.temp);

        // Влажность
        document.getElementById('humidity-value').innerHTML = data.currentReadings.hemi + '<i>%</i>';
        document.getElementById('humidity-difference').textContent = data.differences.hemi || '--';
        document.getElementById('humidity-max').textContent = 'max: ' + data.stats.hemi.max;
        document.getElementById('humidity-min').textContent = 'min: ' + data.stats.hemi.min;

        // Давление
        document.getElementById('pressure-value').innerHTML = data.currentReadings.pressure + '<i>мм. рт. ст.</i>';
        document.getElementById('pressure-value').title = data.currentReadings.pressure_n || '--';
        document.getElementById('pressure-difference').textContent = data.differences.pressure || '--';
        document.getElementById('pressure-max').textContent = 'max: ' + data.stats.pressure.max;
        document.getElementById('pressure-min').textContent = 'min: ' + data.stats.pressure.min;
    }

    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        // Обработчики для кнопок переключения периодов
        document.querySelectorAll('.chart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const period = this.dataset.period;
                switchChartPeriod(period);
            });
        });
        
        // Первая загрузка данных
        loadWeatherData();
        
        // Обновление каждые 6 минут (360000 мс)
        setInterval(loadWeatherData, 360000);
        
        // Обновление таймера каждую секунду
        setInterval(updateTimeout, 1000);
    });
    </script>
</body>
</html>
