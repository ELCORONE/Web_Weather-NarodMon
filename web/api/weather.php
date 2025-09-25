<?php
declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');

// Проверяем, что файл вызывается напрямую, а не включен
if (basename(__FILE__) !== basename($_SERVER['SCRIPT_FILENAME'])) {
    exit('Direct access not allowed');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/cfg/config.php';

$response = [
    'success' => false,
    'error' => '',
    'weatherData' => [],        // Данные за час (10 записей)
    'weatherDayData' => [],     // Данные за сутки (24 записи)
    'currentReadings' => [],
    'differences' => [],
    'stats' => [],
    'statsDay' => [],           // Статистика за сутки
    'cached' => false
];

// Настройки кэширования
$cache_time_hour = 300;        // 5 минут для часовых данных
$cache_time_day = 3600;        // 1 час для суточных данных
$cache_file_hour = $_SERVER['DOCUMENT_ROOT'].'/cache/weather_cache_hour.json';
$cache_file_day = $_SERVER['DOCUMENT_ROOT'].'/cache/weather_cache_day.json';

try {
    // Проверяем есть ли актуальный кэш для часовых данных
    $use_hour_cache = file_exists($cache_file_hour) && (time() - filemtime($cache_file_hour)) < $cache_time_hour;
    $use_day_cache = file_exists($cache_file_day) && (time() - filemtime($cache_file_day)) < $cache_time_day;
    
    if ($use_hour_cache && $use_day_cache) {
        // Используем данные из кэша
        $cached_hour = json_decode(file_get_contents($cache_file_hour), true);
        $cached_day = json_decode(file_get_contents($cache_file_day), true);
        
        if ($cached_hour && $cached_day) {
            $response = array_merge($cached_hour, [
                'weatherDayData' => $cached_day['weatherDayData'],
                'statsDay' => $cached_day['statsDay'],
                'cached' => true
            ]);
            $response['success'] = true;
            
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $sql_connect = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASS, SQL_BASE);
    if (!$sql_connect) {
        throw new Exception("Ошибка подключения к базе данных");
    }
    $sql_connect->set_charset('utf8mb4');

    // Получение данных за час (10 записей)
    if (!$use_hour_cache) {
        $sql_weather_hour = "weather_onehour";
        $query_hour = "SELECT time, temp, hemi, pressure FROM $sql_weather_hour ORDER BY time DESC LIMIT 10";
        $result_hour = $sql_connect->query($query_hour);

        if ($result_hour) {
            $weatherData = [];
            while($row = mysqli_fetch_assoc($result_hour)) {
                $weatherData[] = [
                    'time' => (int)$row['time'],
                    'temp' => (float)$row['temp'],
                    'hemi' => (float)$row['hemi'],
                    'pressure_n' => (float)$row['pressure'],
                    'pressure' => round((float)$row['pressure'] * 0.00750062, 2)
                ];
            }
            
            $weatherData = array_reverse($weatherData);
            
            if (!empty($weatherData)) {
                $currentReadings = end($weatherData);
                $response['weatherData'] = $weatherData;
                $response['currentReadings'] = $currentReadings;
                
                // Расчет статистики за час
                $response['stats'] = [
                    'temp' => [
                        'max' => round(max(array_column($weatherData, 'temp')), 1),
                        'min' => round(min(array_column($weatherData, 'temp')), 1)
                    ],
                    'hemi' => [
                        'max' => max(array_column($weatherData, 'hemi')),
                        'min' => min(array_column($weatherData, 'hemi'))
                    ],
                    'pressure' => [
                        'max' => round(max(array_column($weatherData, 'pressure')), 1),
                        'min' => round(min(array_column($weatherData, 'pressure')), 1)
                    ]
                ];
                
                // Расчет разниц если есть достаточно данных
                if (count($weatherData) >= 10) {
                    $first = $weatherData[0];
                    $last = $weatherData[9];
                    
                    $formatDiff = function($diff) {
                        $symbol = $diff > 0 ? "↑" : "↓";
                        return $symbol . abs(round($diff, 1));
                    };
                    
                    $response['differences'] = [
                        'temp' => $formatDiff($last['temp'] - $first['temp']),
                        'hemi' => $formatDiff($last['hemi'] - $first['hemi']),
                        'pressure' => $formatDiff($last['pressure'] - $first['pressure'])
                    ];
                }
                
                // Сохраняем часовые данные в кэш
                $hour_cache_data = [
                    'weatherData' => $weatherData,
                    'currentReadings' => $currentReadings,
                    'stats' => $response['stats'],
                    'differences' => $response['differences'] ?? []
                ];
                file_put_contents($cache_file_hour, json_encode($hour_cache_data, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    // Получение данных за сутки (24 записи)
    if (!$use_day_cache) {
        $sql_weather_day = "weather_oneday";
        $query_day = "SELECT time, temp, hemi, pressure FROM $sql_weather_day ORDER BY time DESC LIMIT 24";
        $result_day = $sql_connect->query($query_day);

        if ($result_day) {
            $weatherDayData = [];
            while($row = mysqli_fetch_assoc($result_day)) {
                $weatherDayData[] = [
                    'time' => (int)$row['time'],
                    'temp' => (float)$row['temp'],
                    'hemi' => (float)$row['hemi'],
                    'pressure_n' => (float)$row['pressure'],
                    'pressure' => round((float)$row['pressure'] * 0.00750062, 2)
                ];
            }
            
            $weatherDayData = array_reverse($weatherDayData);
            
            if (!empty($weatherDayData)) {
                $response['weatherDayData'] = $weatherDayData;
                
                // Расчет статистики за сутки
                $response['statsDay'] = [
                    'temp' => [
                        'max' => round(max(array_column($weatherDayData, 'temp')), 1),
                        'min' => round(min(array_column($weatherDayData, 'temp')), 1),
                        'avg' => round(array_sum(array_column($weatherDayData, 'temp')) / count($weatherDayData), 1)
                    ],
                    'hemi' => [
                        'max' => max(array_column($weatherDayData, 'hemi')),
                        'min' => min(array_column($weatherDayData, 'hemi')),
                        'avg' => round(array_sum(array_column($weatherDayData, 'hemi')) / count($weatherDayData), 1)
                    ],
                    'pressure' => [
                        'max' => round(max(array_column($weatherDayData, 'pressure')), 1),
                        'min' => round(min(array_column($weatherDayData, 'pressure')), 1),
                        'avg' => round(array_sum(array_column($weatherDayData, 'pressure')) / count($weatherDayData), 1)
                    ]
                ];
                
                // Сохраняем суточные данные в кэш
                $day_cache_data = [
                    'weatherDayData' => $weatherDayData,
                    'statsDay' => $response['statsDay']
                ];
                file_put_contents($cache_file_day, json_encode($day_cache_data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
    
    $response['success'] = true;
    mysqli_close($sql_connect);
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
