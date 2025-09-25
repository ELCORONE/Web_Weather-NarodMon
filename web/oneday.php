<?php
try {
    // Проверяем, сменился ли час
    $lastHour = date('H', $data['times'][8]);
    $currentHour = date('H', $time);
    
    if($lastHour != $currentHour) {
        // Запрос всех записей таблицы дня
        $query_full_day = "SELECT * FROM $sql_weatherday ORDER BY id";
        $stmt_full_day = $pdo->query($query_full_day);
        $rows_day = $stmt_full_day->fetchAll(PDO::FETCH_ASSOC);
        
        // Инициализация массивов
        $data_day = ['temps' => [], 'pressures' => [], 'times' => []];
        
        // Заполнение массивов
        foreach ($rows_day as $row_day) {
            $data_day['temps'][] = $row_day['temp'];
            $data_day['pressures'][] = $row_day['pressure'];
            $data_day['times'][] = $row_day['time'];
        }
        
        // Расчет средних значений за час
        $avg_temp_hour = array_sum($data['temps']) / count($data['temps']);
        $avg_pressures_hour = round(array_sum($data['pressures']) / count($data['pressures']), 2);
        
        // Обновление данных за день
        for ($iday = 0; $iday <= 23; $iday++) {
            if($iday == 23) {
                $data_day['temps'][$iday] = $avg_temp_hour;
                $data_day['pressures'][$iday] = $avg_pressures_hour;
                $data_day['times'][$iday] = $time;
            } else {
                $data_day['temps'][$iday] = $data_day['temps'][$iday + 1];
                $data_day['pressures'][$iday] = $data_day['pressures'][$iday + 1];
                $data_day['times'][$iday] = $data_day['times'][$iday + 1];
            }
            
            // Запись данных с подготовленным запросом
            $update_day = "UPDATE $sql_weatherday SET temp = :temp, 
                          pressure = :pressure, time = :time WHERE id = :id";
            
            $stmt = $pdo->prepare($update_day);
            $stmt->execute([
                ':temp' => $data_day['temps'][$iday],
                ':pressure' => $data_day['pressures'][$iday],
                ':time' => $data_day['times'][$iday],
                ':id' => $iday
            ]);
        }
    }
    
} catch(PDOException $e) {
    error_log("Oneday error: " . $e->getMessage());
}
?>
