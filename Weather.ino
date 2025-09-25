#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiUdp.h>
#include <NTPClient.h>
#include <GyverBME280.h>
#include <GyverHTU21D.h>

// DEFINE константы вместо переменных
#define SSID "WI-FI SSID"
#define PASSWORD "WI-FI PASSWORD"
#define HOST "example.com"
#define TOKEN "ButterFly140"
#define SSL_FINGERPRINT "" // Отпечаток SSL сертификата
#define SERVER_URL "https://example.com/query.php"
#define NTP_SERVER "ntp0.ntp-servers.net"

// Настройка получения времени
#define TIME_OFFSET 35999
#define UPDATE_INTERVAL 3600000

// Настройка для датчика влажности
#define HEATING_DURATION 10000UL	// Время нагрева датчика влажности
#define HUMIDITY_THRESHOLD 85.0		// Мин.влажность для посчета очков высокой влажности
#define HIGH_HUMIDITY_LIMIT 180		// Количество очков необходимое для начала прогрева

// Переменные датчиков
float Temp, Humidity, Pressure;
int HighHumidity = 0;
bool heatingActive = false;
unsigned long heatingStartTime = 0;

// Переменные для отправки
String postData, sTemp, sHumidity, sPressure;
byte send_Data = 0;  // Используем byte вместо int

// Переменные времени
byte tHour, tMinute, tSecond;  // byte достаточно для значений 0-59

// Объекты
WiFiUDP ntpUDP;
GyverHTU21D htu;
GyverBME280 bme;
NTPClient timeClient(ntpUDP, NTP_SERVER, TIME_OFFSET, UPDATE_INTERVAL);

// Первоначальный запуск WeatherStation
void setup() {
	delay(2000);
	Serial.begin(115200);
	
	WiFi.mode(WIFI_OFF);
	delay(1000);
	WiFi.mode(WIFI_STA);
	WiFi.begin(SSID, PASSWORD);
	
	Serial.print("WI-FI: ");
	Serial.println(SSID);
	
	while (WiFi.status() != WL_CONNECTED) {
		Serial.print("-");
		delay(50);
	}
	
	timeClient.begin();
	bme.begin();
	
	if (!htu.begin()) {
		Serial.println(F("HTU21D error"));
	}
	
	Serial.print("Подключено к: ");
	Serial.println(SSID);
	Serial.print("Адрес: ");
	Serial.println(WiFi.localIP());
}

// Основной цикл программы
void loop() {
	set_Time();
	SendData();
	checkHumidity();
}

// Установка времени
void set_Time() {
	timeClient.update();
	tHour = timeClient.getHours();
	tMinute = timeClient.getMinutes();
	tSecond = timeClient.getSeconds();
}

// Проверка: была ли высокой температура в течении 3х часов
void checkHumidity() {
	if (heatingActive) {
		if (millis() - heatingStartTime >= HEATING_DURATION) {
			htu.setHeater(false);
			heatingActive = false;
			HighHumidity = 0;
			Serial.println("Нагрев выключен, счетчик сброшен");
		}
		return;
	}
		
	if (Humidity > HUMIDITY_THRESHOLD) {
		HighHumidity++;
		Serial.print("Высокая влажность! Счетчик: ");
		Serial.println(HighHumidity);
		
		if (HighHumidity >= HIGH_HUMIDITY_LIMIT) {
			htu.setHeater(true);
			heatingActive = true;
			heatingStartTime = millis();
			Serial.println("Включен нагрев на 10 секунд");
		}
	}
}

void SendData() {
	if (tMinute % 6 == 0) {
		if (tSecond != 0) {
			send_Data = 0;
		}
		
		if (tSecond == 0 && send_Data == 0) {
			send_Data = 1;
			
			// Получение данных с датчиков
			Temp = htu.getTemperatureWait();
			Humidity = htu.getHumidityWait();
			Pressure = bme.readPressure();
			
			// Оптимизированное формирование строк
			sTemp = String(Temp);      // 1 знак после запятой
			sHumidity = String(Humidity);
			sPressure = String(Pressure); // Без дробной части для давления
			
			// Оптимизированное формирование POST-данных
			postData = "secretkey=" + String(TOKEN) + 
					  "&temperature=" + sTemp + 
					  "&humidity=" + sHumidity + 
					  "&pressure=" + sPressure;
			
			HTTPClient http;
			http.begin(SERVER_URL);
			http.addHeader("Content-Type", "application/x-www-form-urlencoded");
			
			int httpCode = http.POST(postData);
			String payload = http.getString();
			
			Serial.println("Данные отправлены. Ответ:");
			Serial.print("Код: ");
			Serial.println(httpCode);
			Serial.print("Данные: ");
			Serial.println(payload);
			
			http.end();
		}
		delay(13);
	}
}
