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
                    backgroundColor: 'rgba(78, 205, 134, 0.1)',
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
        const seconds = timeout % 60 - 5;
        
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

    // Функция для вычисления времени до следующего обновления (6 минут : 15 секунд)
    function getTimeUntilNextUpdate() {
        const now = new Date();
        const currentMinutes = now.getMinutes();
        const currentSeconds = now.getSeconds();
        
        // Определяем, сколько минут прошло с начала часа до следующей 6-й минуты
        let minutesToNextUpdate = (6 - (currentMinutes % 6)) % 6;
        if (minutesToNextUpdate === 0 && currentSeconds < 5) {
            // Если мы в нужной 6-й минуте, но до 15 секунд, ждём до 15 секунд
            return (15 - currentSeconds) * 1000;
        }
        
        if (minutesToNextUpdate === 0) {
            // Если уже прошло 15 секунд в 6-й минуте, ждём следующего цикла (6 минут)
            minutesToNextUpdate = 6;
        }
        
        const secondsToNextUpdate = minutesToNextUpdate * 60 - currentSeconds + 5;
        return secondsToNextUpdate * 1000;
    }

    // Функция для запуска периодического обновления
    function startPeriodicUpdate() {
        // Первый запуск через вычисленное время
        setTimeout(() => {
            loadWeatherData(); // Обновляем по расписанию
            setInterval(loadWeatherData, 6 * 60 * 1000); // Дальше каждые 6 минут
        }, getTimeUntilNextUpdate());
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
        
        // Первая загрузка данных сразу при открытии страницы
        loadWeatherData();
        
        // Запускаем периодическое обновление с синхронизацией по времени
        startPeriodicUpdate();
        
        // Обновление таймера каждую секунду
        setInterval(updateTimeout, 1000);
    });
