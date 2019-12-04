#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <Wire.h>
#include <TimeLib.h>

// Подключение экрана
#include <LiquidCrystal_I2C.h>
LiquidCrystal_I2C lcd(0x27,16,2);  // set the LCD address to 0x27 for a 16 chars and 2 line display

// Подключение датчиков
#include <Adafruit_Sensor.h> 
#include <Adafruit_BME280.h>

// Показания с датчиков
float temperature, humidity, pressure, altitude;              //Переменные ввиде чисел
String postData, sTemperature, sHumidity,sPressure,sAltitude;  // Перевод для отправки POST-Запроса

int server_time,send_Data = 0;
int tHour,tMinute,tSecond,tTimer;

// Переменные для подключения к Wi-Fi
const char *ssid = "*****";                  // Точка доступа Wi-Fi
const char *password = "****";            // Пароль к Wi-Fi
const char *host = "****";              // Хост для подключения

// Переменные для работы с таймерами
unsigned long timer1 = 0;                     // Текущее время

String mac_address = WiFi.macAddress();

String token = "ButterFly140";                  // Секретный токен
#define SEALEVELPRESSURE_HPA (1013.25)          // Давление на уровне моря
Adafruit_BME280 bme;                            // Включение датчика как переменную

void setup() {
    Serial.begin(115200);               // Инициализация серийного порта

    bme.begin(0x76);                    // Включение датчика BME280 (адрес датчика 0x76)
    lcd.init();                         // Включение дисплея
    lcd.backlight();
    
    WiFi.mode(WIFI_OFF);                // Отключение от прошлых соединений
    delay(1000);                        // Ждем секунду
    WiFi.mode(WIFI_STA);                // ESP8266 в режиме станции
   
    WiFi.begin(ssid, password);         // Подключение к Wi-Fi
    // Логи
    Serial.print("Connecting");
    lcd.print("Connecting");
    while (WiFi.status() != WL_CONNECTED) {
        lcd.print(".");
        delay(500);
    }
    
    getTimeFromServer();                  // Получение времени в unix-формате с сервера
    tTimer = minute()+1;                  // Текущая минута +1 (необходимо для таймера)
    // Логирование подключения в Serial
    Serial.print("Подключено к : ");
    Serial.println(ssid);
    Serial.print("Адрес: ");
    Serial.println(WiFi.localIP());    
    lcd.clear();
}

void loop() {
    tHour = hour();     // Час в переменную
    tMinute = minute(); // Минута в переменную
    tSecond = second(); // Секунда в переменную
    SData(); // Отправка данных на сервер каждую шестую минуту

    // Обновление времени на дисплее каждую минуту
    if(tMinute == tTimer){
      tTimer++;
      if(tMinute == 59) tTimer = 0;
      char lcd_time_i[10];
      snprintf(lcd_time_i, sizeof(lcd_time_i), "%02d:%02d",hour(),minute());
      lcd.setCursor(0,0);
      Serial.println("Прошла минута");
      lcd.print(lcd_time_i);
    }
}

// Отправка показаний с датчика на сервер
void SData(){
  if(tMinute % 6 == 0){                 // Каждую 6 (шестую) минуту
    if(tSecond != 0) send_Data = 0;
    if(tSecond == 0 && send_Data == 0){ 
      send_Data = 1;
  
      // Получение данных с датчика
      temperature = bme.readTemperature();
      humidity = bme.readHumidity();
      pressure = bme.readPressure();
      //altitude = bme.readAltitude(SEALEVELPRESSURE_HPA);

      //Создания HTTP-клиента
      HTTPClient http;
      
      // Перевод показаний с датчика в строку
      sTemperature = String(temperature);
      sHumidity = String(humidity);
      sPressure = String(pressure);
      
      // Строчка отправки POST-запроса
      postData = "secretkey=" + token + "&mac_address=" + mac_address + "&temperature=" + sTemperature + "&humidity=" + sHumidity + "&pressure=" + sPressure;
      // Адрес для отправки POST-запрос
      http.begin("/query.php");
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      // Отправка запроса на сервер
      int httpCode = http.POST(postData);
      // Получение ответа с сервера
      String payload = http.getString();

      Serial.println("Данные с датчиков отправлены: ответы->");
      Serial.println(httpCode);   // Логи в серийный порт
      Serial.println(payload);    // Логи в серийный порт
      http.end();                   // Закрытие соединения
     }
    delay(50);
  }
}

void getTimeFromServer(){
    HTTPClient http_start;
    http_start.begin("/query.php");
    http_start.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpCode_start = http_start.POST("secretkey=QueryTime");
    String payload_start = http_start.getString();
    server_time = payload_start.toInt();
    setTime(server_time);
}
