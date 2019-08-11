#include <ESP8266WiFi.h>
//#include <WiFiClient.h> 
//#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <Wire.h> 
#include <Adafruit_Sensor.h> 
#include <Adafruit_BME280.h> 

const char *ssid = "********";                           // Точка доступа Wi-Fi
const char *password = "********";    й          // Пароль к Wi-Fi
const char *host = "********";              // Хост для подключения
int period_send = 6*60*1000;                    // Период отправки данных = 6 минут * 60 секунд * 1000 милисекунд
unsigned long time_now = 0;                     // Текущее время
float temperature, humidity, pressure, altitude;// Переменные с датчика
String mac_address = WiFi.macAddress();

String token = "********";                   // Проверочный токен
#define SEALEVELPRESSURE_HPA (1013.25)          // Ваще не ебу
Adafruit_BME280 bme;                            // Включение датчика как переменную

void setup() {
    delay(1000);
    Serial.begin(115200);               // Старт Серийного последовательного порта
    bme.begin(0x76);                    // Включение датчика BME280
    WiFi.mode(WIFI_OFF);                // Отключение от прошлых соединений
    delay(1000);                        // Ждем секунду
    WiFi.mode(WIFI_STA);                // ESP8266 в режим клиента
    WiFi.begin(ssid, password);         // Подключение к Wi-Fi
    Serial.println("");
    Serial.print("Connecting");         // Процедура подключения
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("");
    Serial.print("Подключено к : ");
    Serial.println(ssid);
    Serial.print("Адрес: ");
    Serial.println(WiFi.localIP());     // Выдает в последовательный порт локальный IP-адрес ESP8266
}

void loop() {
    if(millis() > time_now + period_send){
      time_now = millis();
      // Получение данных с датчика и запись в переменные ↓
      temperature = bme.readTemperature(); 
      humidity = bme.readHumidity(); 
      pressure = bme.readPressure(); 
      pressure = pressure /** 0.00750062*/;
      altitude = bme.readAltitude(SEALEVELPRESSURE_HPA);
      HTTPClient http;                                            // 
      String postData,Stemp, SHemi,Spresure,SAltitude;            // Переменные отсылаемые в качестве текста, если это не числа
      Stemp = String(temperature);
      SHemi = String(humidity);
      Spresure = String(pressure);
      SAltitude = String(altitude);
      // Строчка отправки POST-запроса
      postData = "secretkey=" + token + "&mac_address=" + mac_address + "&temperature=" + Stemp + "&humidity=" + SHemi + "&pressure=" + Spresure + "&altitude=" + SAltitude;
      http.begin("http://********/query.php");                      // Адрес до испольнительного файла на сервере куда будет отправлен POST-запрос
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      int httpCode = http.POST(postData);                          // Сбор строки в запрос
      String payload = http.getString();                           // Преобразования строки в запрос
      Serial.println(httpCode);                                    // Логи в серийный порт
      Serial.println(payload);                                     // Логи в серийный порт
      http.end();                                                  // Закрытие соединения
    }
}
