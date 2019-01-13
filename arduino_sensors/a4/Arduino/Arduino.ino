// библиотека для работы с датчиком DHT11
#include <DHT.h>
// библиотека для работы JSON нотациями
#include <ArduinoJson.h>
#include <Wire.h>
#include <SPI.h>
//#include <Adafruit_Sensor.h>
#include <Adafruit_BMP280.h>

// создаём объект класса DHT22 и пераём номер пина к которому подкючён датчик
#define DHT22PIN 7
#define DHT22TYPE DHT22
#define BMP_SCK 13
#define BMP_MISO 12
#define BMP_MOSI 11
#define BMP_CS 10
int Relay1 = 4;
//int Relay2 = 5;
// init class DHT for DHT22
DHT dht22(DHT22PIN, DHT22TYPE);

//Adafruit_BMP280 bme; //  по шине I2C
Adafruit_BMP280 bme(BMP_CS); // работаем по шине  hardware SPI !!!
//Adafruit_BMP280 bme(BMP_CS, BMP_MOSI, BMP_MISO, BMP_SCK);

/*
 * Arduino MINI
 * 4 - relay1
 * 5 - relay2 - not used
 * 8 - dht11  - not used
 * 7 - dht22
*/

void setup() {
  Serial.begin(115200);
  // initialize digital pin LED_BUILTIN as an output.
  pinMode(LED_BUILTIN, OUTPUT);
  pinMode(Relay1, OUTPUT);
  // pinMode(Relay2, OUTPUT);
  dht22.begin();
  bme.begin();

//  for init MB280 sensor
//  if (!bme.begin()) {
//    Serial.println("Could not find a valid BMP280 sensor, check wiring!");
//    while (1);
//  }
}

// the loop function runs over and over again forever
void loop() {
  digitalWrite(LED_BUILTIN, HIGH);
  delay(10000); // 10000
  digitalWrite(LED_BUILTIN, LOW);
  // Reading temperature or humidity takes about250 milliseconds!
  float t22  = dht22.readTemperature();
  float h22  = dht22.readHumidity();
  float p280 = bme.readPressure();
//  Serial.print(h22);
//  Serial.print(t22);
//  Serial.print(bme.readPressure());
//  Serial.println(" Pa");
  Serial.println("t="+String(t22)+"&h="+String(h22)+"&p="+String(p280));

  delay(45000); // 45000 !!!

  Serial.println("GET");

  String request;

  delay(5000);

  if(Serial.available()>0){
    request = "";
    while (Serial.available() > 0){
      request = Serial.readStringUntil('\n');// '\n'
    }
    //  request = Serial.readStringUntil(1000);// '\n'
    Serial.println(request); // ***

    delay(5000);

    DynamicJsonBuffer jsonBuffer;
    JsonObject& root = jsonBuffer.parseObject(request);
    // Test if parsing succeeds.
//    if (!root.success()) {
//      Serial.println("parseObject() failed");
//      return;
//    }
    const char* ralay1 = root["a4"]["relay1"];  // transporting to setup(){}
  //  const char* ralay2 = root["a4"]["relay2"];  // to

//    Serial.println("relay1 - "+String(ralay1));
//    Serial.println("relay2 - "+String(ralay2));

    // start logic relay moduls
    if(String(ralay1).indexOf("on") >= 0) {
      ralay1 = "on";
    }
//    if(String(ralay2).indexOf("on") >= 0) {
//      ralay2 = "on";
//    }
    else if(String(ralay1).indexOf("off") >= 0) {
      ralay1 = "off";
    }
//    if(String(ralay2).indexOf("off") >= 0) {
//      ralay2 = "off";
//    }
    // relay 1
    if(ralay1 == "on"){
//      Serial.println("ralay1 - turn on");
        digitalWrite(LED_BUILTIN, HIGH);   // turn the LED on (HIGH is the voltage level)
        digitalWrite(Relay1, HIGH);
        delay(1000);
    }
    if(ralay1 == "off"){
//      Serial.println("ralay1 - turn off");
        digitalWrite(LED_BUILTIN, LOW);    // turn the LED off by making the voltage LOW
        digitalWrite(Relay1, LOW);
        delay(1000);
    }
    // relay 2
//    if(ralay2 == "on"){
////      Serial.println("ralay2 - turn on");
//        digitalWrite(LED_BUILTIN, HIGH);   // turn the LED on (HIGH is the voltage level)
//        digitalWrite(Relay2, HIGH);
//        delay(1000);
//    }
//    else if(ralay2 == "off"){
////      Serial.println("ralay2 - turn off");
//        digitalWrite(LED_BUILTIN, LOW);    // turn the LED off by making the voltage LOW
//        digitalWrite(Relay2, LOW);
//        delay(1000);
//    }

    delay(10000); // 10000
  }

}
