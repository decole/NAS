// библиотека для работы с датчиком DHT11
#include <DHT.h>
// библиотека для работы JSON нотациями
#include <ArduinoJson.h>
#include <Wire.h>
#include <SPI.h>
//#include <Adafruit_Sensor.h>
#include <Adafruit_BMP280.h>

#define BMP_SCK 13
#define BMP_MISO 12
#define BMP_MOSI 11
#define BMP_CS 10

//Adafruit_BMP280 bme; //  работаем по шине I2C
Adafruit_BMP280 bme(BMP_CS); // работаем по шине  hardware SPI
//Adafruit_BMP280 bme(BMP_CS, BMP_MOSI, BMP_MISO, BMP_SCK);

// создаём объект класса DHT11 и пераём номер пина к которому подкючён датчик
#define DHTPIN 8
#define DHT22PIN 7
#define DHT22TYPE DHT22
#define DHTTYPE DHT11
// Reley ports
int Relay1 = 4;
//int Relay2 = 5;
// init class DHT
DHT dht(DHTPIN, DHTTYPE);
DHT dht22(DHT22PIN, DHT22TYPE);
/*
 * Arduino MINI
 * 4 - relay1
 * 5 - relay2 - not used
 * 8 - dht11
 * 7 - dht22
*/

void setup() {
  // start serial port
  Serial.begin(115200);
  // initialize digital pin LED_BUILTIN as an output.
  pinMode(LED_BUILTIN, OUTPUT);
  pinMode(Relay1, OUTPUT);
 // pinMode(Relay2, OUTPUT);
  dht.begin();
  dht22.begin();
  if (!bme.begin()) {
    Serial.println("Could not find a valid BMP280 sensor, check wiring!");
    while (1);
  }

}

// the loop function runs over and over again forever
void loop() {
  /*delay(10000);*/
  delay(2000);
//    float h = dht.readHumidity();
//    float t = dht.readTemperature();
    float t22 = dht22.readTemperature();
    float h22 = dht22.readHumidity();

  Serial.print("DHT22: ");
  Serial.print(h22);
  Serial.print(" %\t");
  Serial.print(t22);
  Serial.println(" *C ");


    Serial.print("Pressure = ");
    Serial.print(bme.readPressure());
    Serial.println(" Pa");


    Serial.println();
}
