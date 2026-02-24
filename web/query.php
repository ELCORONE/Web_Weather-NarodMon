<?php
// Настройки запросов
require_once $_SERVER['DOCUMENT_ROOT'].'/cfg/config.php';
require_once "function.php";

$time = time();

// Если пришли значения с ESP8266
if(!empty($_POST['secretkey']) && $_POST['secretkey'] == "ButterFly140"){
    $sql_weather = "weather_onehour";
    $sql_weatherday = "weather_oneday";
    
    // Валидация и фильтрация входных данных
    $temperature = filter_var($_POST['temperature'], FILTER_VALIDATE_FLOAT);
    $humidity = filter_var($_POST['humidity'], FILTER_VALIDATE_FLOAT);
    $pressure = filter_var($_POST['pressure'], FILTER_VALIDATE_FLOAT);
	$highhumidity = $_POST['HeaterScore'];
    
    if($temperature === false || $humidity === false || $pressure === false) {
        die('Invalid input data');
    }
    
    if($pressure < 87000 || $pressure > 108000) {
        die();
    }
	    
    try {
        // Подключение PDO
        $pdo = new PDO("mysql:host=".SQL_HOST.";dbname=".SQL_BASE.";charset=utf8", 
                      SQL_USER, SQL_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Проверка времени с последнего обновления
        $checkTimeStmt = $pdo->prepare("SELECT time FROM $sql_weather WHERE id = 9");
        $checkTimeStmt->execute();
        $lastTime = $checkTimeStmt->fetchColumn();
        
        // Если прошло больше 3700 секунд - очищаем таблицу текущими значениями
        if(($time - $lastTime) > 3700) {
            $refreshStmt = $pdo->prepare("UPDATE $sql_weather SET temp = :temp, 
                                        hemi = :hemi, pressure = :pressure, 
                                        time = :time, delta = 0");
            $refreshStmt->execute([
                ':temp' => $temperature,
                ':hemi' => $humidity,
                ':pressure' => $pressure,
                ':time' => $time
            ]);
        } else {
            // Работа с таблицами
            require_once 'onehour.php';
            require_once 'oneday.php';
            require_once 'narodmon.php';
			
			/*$log_entry = date("Y-m-d H:i:s") . " / " . $temperature . " / " . $humidity . " / " . $highhumidity . " / " . $data["deltas"][9] . PHP_EOL;
			file_put_contents("log.txt", $log_entry, FILE_APPEND | LOCK_EX);*/
        }
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        die('Database connection error');
    }
} else {
    http_response_code(403);
    echo "<center><span style='font-size:10em;border:1px solid red'>ИДИ В ПИЗДУ / BUENOS NOCHES PEDRILAS</span></center>";
}
?>
