// библиотека для работы с датчиком DHT11
#include <DHT.h>
// библиотека для работы JSON нотациями
#include <ArduinoJson.h>

// создаём объект класса DHT11 и пераём номер пина к которому подкючён датчик
#define DHTPIN 8
#define DHTTYPE DHT11
// Reley ports
int Relay1 = 4;
//int Relay2 = 5;
// init class DHT
DHT dht(DHTPIN, DHTTYPE);

/*
 * Arduino MINI
 * 4 - relay1
 * 5 - relay2 - not used
 * 8 - dht11
*/

void setup() {
  // initialize digital pin LED_BUILTIN as an output.
  pinMode(LED_BUILTIN, OUTPUT);
  pinMode(Relay1, OUTPUT);
 // pinMode(Relay2, OUTPUT);
  dht.begin();
  // start serial port
  Serial.begin(115200);
}

// the loop function runs over and over again forever
void loop() {
  delay(10000);
  // Reading temperature or humidity takes about 250 milliseconds!
  // Sensor readings may also be up to 2 seconds 'old' (its a very slow sensor)
  float h = dht.readHumidity();
  // Read temperature as Celsius (the default)
  float t = dht.readTemperature();

  // p - persistant - in this scetch not availible
  // in future make new scetch where init persistant sensor
  Serial.println("t="+String(t)+"&h="+String(h)+"&p=");

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
    const char* ralay1 = root["a3"]["relay1"];  // transporting to setup(){}
  //  const char* ralay2 = root["a3"]["relay2"];  // to

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

    delay(10000);
  }

}
