<?php
function analyzeWeatherTrend($data, $current_temp, $current_humidity, $current_pressure) {
    $trend = [
        'message' => '',
        'priority' => 0, // 0-нет уведомления, 1-инфо, 2-внимание, 3-предупреждение
        'type' => ''
    ];
    
    $current_delta = $data['deltas'][9]; // Последнее значение дельты
    $avg_delta = array_sum($data['deltas']) / count($data['deltas']);
    
    // Анализ тренда давления за последние 3 периода (18 минут)
    $recent_deltas = array_slice($data['deltas'], -3);
    $recent_trend = array_sum($recent_deltas) / count($recent_deltas);
    
    // Анализ изменения температуры
    $temp_change = $current_temp - $data['temps'][0];
    $humidity_change = $current_humidity - $data['hemis'][0];
    
    // 📊 УСЛОВИЯ ДЛЯ УВЕДОМЛЕНИЙ
    
    // 1. СИЛЬНОЕ УХУДШЕНИЕ (ШТОРМОВОЕ ПРЕДУПРЕЖДЕНИЕ)
    if ($current_delta < -300 || ($current_delta < -200 && $humidity_change > 15)) {
        $trend['message'] = "⚡️ ШТОРМОВОЕ ПРЕДУПРЕЖДЕНИЕ! Давление резко падает (" . round($current_delta) . " Па/ч). Ожидается сильная гроза/шторм в течение часа.";
        $trend['priority'] = 3;
        $trend['type'] = 'storm_warning';
    }
    // 2. БЫСТРОЕ УХУДШЕНИЕ
    elseif ($current_delta < -150 && $recent_trend < -100) {
        if ($current_humidity > 80) {
            $trend['message'] = "🌧️ СКОРО ДОЖДЬ! Давление падает (" . round($current_delta) . " Па/ч), влажность высокая. Дождь начнется в течение 30-60 минут.";
            $trend['priority'] = 2;
            $trend['type'] = 'rain_soon';
        } else {
            $trend['message'] = "☁️ ПОГОДА УХУДШАЕТСЯ. Давление падает (" . round($current_delta) . " Па/ч). Ожидается облачность.";
            $trend['priority'] = 1;
            $trend['type'] = 'weather_worsening';
        }
    }
    // 3. СИЛЬНОЕ УЛУЧШЕНИЕ
    elseif ($current_delta > 300 || ($current_delta > 200 && $temp_change > 2)) {
        $trend['message'] = "☀️ ПОГОДА РЕЗКО УЛУЧШАЕТСЯ! Давление растет (" . round($current_delta) . " Па/ч). Ожидается ясная солнечная погода.";
        $trend['priority'] = 2;
        $trend['type'] = 'rapid_improvement';
    }
    // 4. УЛУЧШЕНИЕ ПОСЛЕ ДОЖДЯ
    elseif ($current_delta > 100 && $recent_trend > 50 && $humidity_change < -10) {
        $trend['message'] = "⛅️ ПОГОДА УЛУЧШАЕТСЯ. Давление растет (" . round($current_delta) . " Па/ч), влажность снижается. Дождь скоро закончится.";
        $trend['priority'] = 1;
        $trend['type'] = 'improving_after_rain';
    }
    // 5. СТАБИЛИЗАЦИЯ ПОСЛЕ ИЗМЕНЕНИЙ
    elseif (abs($current_delta) < 50 && abs($avg_delta) > 100 && $recent_trend < 30) {
        $trend['message'] = "🔸 ПОГОДА СТАБИЛИЗИРУЕТСЯ. Резкие изменения давления прекратились.";
        $trend['priority'] = 1;
        $trend['type'] = 'stabilization';
    }
    // 6. ВЫСОКАЯ ВЛАЖНОСТЬ + ПАДЕНИЕ ДАВЛЕНИЯ
    elseif ($current_humidity > 85 && $current_delta < -80) {
        $trend['message'] = "💧 ВЫСОКАЯ ВЛАЖНОСТЬ И ПАДЕНИЕ ДАВЛЕНИЯ. Возможен туман или моросящий дождь.";
        $trend['priority'] = 1;
        $trend['type'] = 'high_humidity_warning';
    }
    // 7. РЕЗКОЕ ПОНИЖЕНИЕ ТЕМПЕРАТУРЫ
    elseif ($temp_change < -3 && abs($current_delta) < 100) {
        $trend['message'] = "❄️ РЕЗКОЕ ПОХОЛОДАНИЕ. Температура упала на " . round(abs($temp_change), 1) . "°C за час.";
        $trend['priority'] = 1;
        $trend['type'] = 'temperature_drop';
    }
    
    return $trend;
}
?>
