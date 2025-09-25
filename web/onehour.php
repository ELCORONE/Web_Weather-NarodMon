<?php
try {
    // Запрос всех записей таблицы с подготовленным запросом
    $query_full = "SELECT * FROM $sql_weather ORDER BY id";
    $stmt_full = $pdo->query($query_full);
    $rows = $stmt_full->fetchAll(PDO::FETCH_ASSOC);
    
    // Инициализация массивов
    $data = [
        'ids' => [], 'temps' => [], 'hemis' => [], 
        'pressures' => [], 'times' => [], 'deltas' => []
    ];
    
    // Заполнение массивов
    foreach ($rows as $row) {
        $data['ids'][] = $row['id'];
        $data['temps'][] = $row['temp'];
        $data['hemis'][] = $row['hemi'];
        $data['pressures'][] = $row['pressure'];
        $data['times'][] = $row['time'];
        $data['deltas'][] = $row['delta'];
    }
    
    // Расчет предсказания и обновление данных
    for ($i = 0; $i < 10; $i++) {
        if($i == 9) {
            // Записываем текущие значения
            $data['temps'][$i] = $temperature;
            $data['hemis'][$i] = $humidity;
            $data['pressures'][$i] = $pressure;
            $data['times'][$i] = $time;
            
            // Расчет предсказания погоды
            $sumX = $sumY = $sumX2 = $sumXY = 0;
            
            for ($ix = 0; $ix < 10; $ix++) {
                $sumX += $data['ids'][$ix];
                $sumY += $data['pressures'][$ix];
                $sumX2 += $data['ids'][$ix] * $data['ids'][$ix];
                $sumXY += $data['ids'][$ix] * $data['pressures'][$ix];
            }
            
            // Расчет коэффициента наклона
            $numerator = 10 * $sumXY - $sumX * $sumY;
            $denominator = 10 * $sumX2 - $sumX * $sumX;
            
            if($denominator != 0) {
                $factor = $numerator / $denominator;
                $dpressure = $factor * 10;
                $data['deltas'][$i] = round($dpressure, 2);
            } else {
                $data['deltas'][$i] = 0;
            }
        } else {
            // Сдвигаем значения
            $data['temps'][$i] = $data['temps'][$i + 1];
            $data['hemis'][$i] = $data['hemis'][$i + 1];
            $data['pressures'][$i] = $data['pressures'][$i + 1];
            $data['times'][$i] = $data['times'][$i + 1];
            $data['deltas'][$i] = $data['deltas'][$i + 1];
        }
        
        // Запись данных с подготовленным запросом
        $update_hour = "UPDATE $sql_weather SET temp = :temp, hemi = :hemi, 
                       pressure = :pressure, time = :time, delta = :delta 
                       WHERE id = :id";
        
        $stmt = $pdo->prepare($update_hour);
        $stmt->execute([
            ':temp' => $data['temps'][$i],
            ':hemi' => $data['hemis'][$i],
            ':pressure' => $data['pressures'][$i],
            ':time' => $data['times'][$i],
            ':delta' => $data['deltas'][$i],
            ':id' => $i
        ]);
    }
    
} catch(PDOException $e) {
    error_log("Onehour error: " . $e->getMessage());
}
?>
