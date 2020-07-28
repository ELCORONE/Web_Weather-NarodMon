#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <Wire.h>
#include <TimeLib.h>
#include <iarduino_OLED.h>  

// Подключение экрана

// Подключение датчиков
#include <Adafruit_Sensor.h> 
#include <Adafruit_BME280.h>

// Показания с датчиков
float temperature, humidity, pressure, altitude;               //Переменные ввиде чисел
String postData, sTemperature, sHumidity,sPressure,sAltitude;  // Перевод для отправки POST-Запроса

int server_time,send_Data = 0;
int tHour,tMinute,tSecond,tTimer;
extern uint8_t MediumFontRus[];

// Переменные для подключения к Wi-Fi
const char *ssid = "";                  // Точка доступа Wi-Fi
const char *password = "";            // Пароль к Wi-Fi
const char *webhost = "";              // Хост для подключения
const char *sslhost = "";              // Хост для подключения

// Переменные для работы с таймерами
unsigned long timer1 = 0;                     // Текущее время

String mac_address = WiFi.macAddress();

String token = "";                  // Проверочный токен
#define SEALEVELPRESSURE_HPA (1013.25)          // Ваще не ебу
Adafruit_BME280 bme;                            // Включение датчика как переменную
iarduino_OLED myOLED(0x3C);

void setup() {
    delay(1000);
    Serial.begin(115200);               // Инициализация серийного порта
    myOLED.setFont(MediumFontRus);
    myOLED.begin();
    bme.begin(0x76);                    // Включение датчика BME280
    myOLED.setCoding(TXT_UTF8);
    WiFi.mode(WIFI_OFF);                // Отключение от прошлых соединений
    delay(1000);                        // Ждем секунду
    WiFi.mode(WIFI_STA);                // ESP8266 в режим клиента
   
    WiFi.begin(ssid, password);         // Подключение к Wi-Fi
    Serial.print("Connecting");         // Процедура подключения
   
    while (WiFi.status() != WL_CONNECTED) {
        Serial.print("/");
        delay(500);
       // lcd.clear();
    }

    getTimeToServer();
    tTimer = minute()+1;
    Serial.println(server_time);

    Serial.print("Подключено к : ");
    Serial.println(ssid);
    Serial.print("Адрес: ");
    Serial.println(WiFi.localIP());    
}

void loop() {
    tHour = hour();
    tMinute = minute();
    tSecond = second();
    SData(); // Отправка данных на сервер каждую шестую минуту

    if(tMinute == tTimer){
     
      tTimer++;
      if(tMinute == 59) tTimer = 0;
      char lcd_time_i[10];
      snprintf(lcd_time_i, sizeof(lcd_time_i), "%02d:%02d",hour(),minute());
      Serial.println(lcd_time_i);
      myOLED.print(lcd_time_i,0, 16);
      myOLED.print(sTemperature,0, 32);
      myOLED.print(sHumidity,0, 48);
      myOLED.print(sPressure,0, 64);
    }
}

void SData(){
  if(tMinute % 6 == 0){
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
      http.begin(webhost+"/weather/query.php");
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

void getTimeToServer(){
    HTTPClient http_start;
    http_start.begin(webhost+"/weather/query.php");
    http_start.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpCode_start = http_start.POST("secretkey=QueryTime");
    String payload_start = http_start.getString();
    server_time = payload_start.toInt();
    setTime(server_time);
}
