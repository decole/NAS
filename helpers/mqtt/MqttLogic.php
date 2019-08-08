<?php
namespace app\helpers\mqtt;

use app\helpers\telegram\TelegramLogic;
use app\models\Arduinoiot;
use app\models\Mqtt;
use DateInterval;
use DateTime;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidRouteException;
use yii\console\Exception;


/**
 * This class make logic on site from command class Mqtt
 */
class MqttLogic extends BaseObject
{
    public $host = '192.168.1.5';
    public $port = 1883;
    public $time = 60;
    private $client;
    private $isConnect = false;
    private $alarmTemper = 43;
    private $periodicTime = 1800; // период произведения анализа в методе process

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->client = new \Mosquitto\Client();
        $this->client->connect($this->host, $this->port, 5);
        // https://mosquitto-php.readthedocs.io/en/latest/client.html#Mosquitto\Client::onConnect
        $this->client->onConnect(function ($rc){
            if($rc === 0){
                $this->isConnect = true;
            }
            else {
                $this->isConnect = false;
            }

        });
        $this->client->onDisconnect(function (){
            $this->isConnect = false;
        });
        register_shutdown_function([$this, 'disconnect']);

    }

    public function listen()
    {
        $this->client->subscribe('#', 1);
        $this->client->onMessage([$this, 'process']);
        while(true) {
            $this->client->loop(10);
        }

    }

    /**
     * Sending data to topic on mqtt protocol
     * @param $topic $data
     * @return mixed
     */
    public function post($topic, $data)
    {
        $this->client->publish($topic, $data, 1, 0);
        return $data;
    }

    /**
     * Disconnect mqtt connection in lib
     */
    public function disconnect()
    {
        if($this->isConnect){
            $this->client->disconnect();
        }
    }

    /**
     * @param $message
     * @throws \yii\base\InvalidConfigException     *
     */
    public function process($message){
        $options = static::listTopics()[$message->topic] ?? null;
        if($options) {
            $this->setCacheMqtt($message->topic, $message->payload);
            $this->analising($message, $options);
            if(time() > $this->getCacheMqtt('analisingTime')) {
                $this->checkAnomaly();
                $this->saveToDB();
                $this->setCacheMqtt('analisingTime', time() + $this->periodicTime);
            }
        }

    }

    /**
     * Analising mqtt payload on current topic in memcache and recording one in 1 minute
     * @param $message
     * @param $options
     */
    public function analising($message, $options): void
    {
        // validate register topics
        if($options && isset($options['message']) && is_callable($options['message'])) {
            // if send changing command in mqtt mobile app
            if($options['type'] === 'swift') {
                $this->changeState($options, $message);
            }
            if ($options['type'] === 'sensor') {
                $this->checkFire($options, $message);
                $this->leakage($options, $message);
            }
            // check modules is online, needed from checkers - alice assistant and smart watering
            $this->isOnline($message);
        }

    }

    /**
     * Get cache on memcache
     *
     * @param $key
     * @return mixed|string
     */
    public function getCacheMqtt($key)
    {
        $cache = Yii::$app->cache;
        return $cache->get($key);

    }

    /**
     * Set cache to memcache
     *
     * @param $key
     * @param $value
     */
    public function setCacheMqtt($key, $value)
    {
        $cache = Yii::$app->cache;
        $cache->set($key, $value);
    }

    /**
     * Delete cache value to memcache
     *
     * @param $key
     */
    public function deleteCacheMqtt($key)
    {
        $cache = Yii::$app->cache;
        $cache->delete($key);
    }

    /**
     * checking sensors to anomaly payload on memcached saved data
     *
     * @param $options
     * @param $message
     */
    private function checkAnomaly(): void
    {
        $options = static::listTopics();
        $message = null;
        foreach ($options as $topic => $option){
            $payload = $this->getCacheMqtt($topic);
            if (!empty($payload) && $option['type'] === 'sensor' &&
                ((isset($option['condition']['min']) && $option['condition']['min'] > $payload) ||
                (isset($option['condition']['max']) && $option['condition']['max'] < $payload))
            ) {
                $message .= $option['message']($payload) . PHP_EOL;
            }
        }
        if($message !== null){
            $this->mailing($message, $options);
        }

    }

    /**
     * if temperature > $alarmTemper - alarm any time !!!
     *
     * @param $options
     * @param $message
     */
    private function checkFire($options, $message): void
    {
        if(($options['format'] === '°C') && ($message->payload > $this->alarmTemper)) {
            $this->mailing($options['message']($message->payload), $options);
        }

    }

    /**
     * Only from smart water swifts!
     * if have leakage sensing - turn off all water swifts
     *
     * @param $options
     * @param $message
     */
    private function leakage($options, $message): void
    {
        if(($options['format'] === 'leakage') && ($message->payload == $options['condition']['warning'])) {
            $this->post('water/alarm', '1');
            $this->mailing($options['message']($message->payload), $options);

            sleep(1);
            $this->post('water/alarm', '0');
        }

    }

    /**
     * Checking sensors to anomaly payload
     *
     * @param $options
     * @param $message
     */
    private function changeState($options, $message): void
    {
        if (isset($options['condition']['on'], $options['condition']['off']) ) {
            if ($message->payload == 0 || $message->payload == 1) {
                // safe new state swift
                $this->saveState($options['arduionIotId'], $message->payload);
            }
            else {
                $this->mailing('Ошибка '.$message->topic.' - прислал плохое значение'.$message->payload, $options);
            }
        }

    }


    /**
     * Save state relay
     *
     * @param $id
     * @param $value
     */
    public function saveState($id, $value): void
    {
        $customer = Arduinoiot::find()->where(['id' => $id])->limit(1)->one();
        $customer->relay1 = $value;
        $customer->save();

    }


    /**
     * saving to DB all register topics on memcached dates
     */
    private function saveToDB(): void
    {
        $cache = Yii::$app->cache;
        $options = MqttLogic::listTopics();
        foreach ($options as $topic => $option){
            if($option['type'] === 'sensor') {
                $payload = $cache->get($topic);
                if($payload !== false) {
                    $customer = new Mqtt();
                    $customer->topic = $topic;
                    $customer->payload = $payload;
                    $customer->datetime = date('Y-m-d H:i:s');
                    //Yii::$app->formatter->asDate(date('Y-m-d H:i:s'), 'yyyy-MM-dd HH:mm:ss');
                    if (!$customer->save()) {
                        echo "not added payload \n";
                        var_dump('topic:' . $topic . ', payload:' . $payload);
                    }
                }
            }
        }

    }

    /**
     * Sending specific massage to telegram from needed users, inserting in options['users']
     *
     * @param $massage
     * @param $options
     */
    public function mailing($massage, $options): void
    {
        if(empty($options['users'] ?? null)) {
            $options['users'] = ['decole'];
        }

        foreach ($options['users'] as $user) {
            $telegram = new TelegramLogic();
            $telegram->sendByUser($massage, $user);
        }

    }

    /**
     * Check topics to not valid data
     *
     * @param $topic
     * @param $checkTopic
     */
    public function checkAlarmTopic($topic, $checkTopic): void
    {
        $options = $this::listTopics();
        $stateTopic = $this->getCacheMqtt($topic);
        $checkStateTopic = $this->getCacheMqtt($checkTopic);
        if(empty($this->getCacheMqtt('checkStateTopicAlarm'))) {
            $this->setCacheMqtt(
                'checkStateTopicAlarm',
                [$topic => strtotime('+3 minutes')]
            );
        }

        $topicsAlarm = $this->getCacheMqtt('checkStateTopicAlarm');
        if(empty($topicsAlarm[$topic])) {
            $this->setCacheMqtt(
                'checkStateTopicAlarm',
                array_merge(
                    $topicsAlarm,
                    [$topic => strtotime('+3 minutes')]
                )
            );
        }

        $timeNow = time();
        if(isset($topicsAlarm[$topic]) && $timeNow > $topicsAlarm[$topic]) {
            $massage = $topic
                . ' - wrong state from check state [' . $stateTopic . ' - ' . $checkStateTopic . ']'
                . date("d-m-Y H:i:s") . PHP_EOL;
            $this->mailing($massage, $options);
        }

    }


    /**
     * Delete old alarm notification
     */
    public function checkOldMemcachedAlarmTopics(): void
    {
        $topicsAlarm = $this->getCacheMqtt('checkStateTopicAlarm');
        if(is_array($topicsAlarm)) {
            foreach ($topicsAlarm as $topic=>$topicTime){
                if(time() > $topicTime ) {
                    unset($topicsAlarm[$topic]);
                }
            }
            if(empty($topicsAlarm)) {
                $this->deleteCacheMqtt('checkStateTopicAlarm');
            }
            if (!empty($topicsAlarm)) {
                $this->setCacheMqtt('checkStateTopicAlarm',$topicsAlarm);
            }
        }

    }

    /**
     * List sensors state for Telegram and Alice
     *
     * @return string
     */
    public function sensorStatus($format = "standart")
    {
        $string = 'Данные по сенсорам:'.PHP_EOL;
        $topics = MqttLogic::listTopics();
        $nameOfTopics = Mqtt::getSensorNames();

        if ($format === "standart" && $format !== "telegram" && $format !== "alice") {
            foreach ($topics as $topic => $options) {
                if ($options['type'] === 'sensor') {
                    $payload = $this->getCacheMqtt($topic);

                    if ($options['format'] === 'leakage'){
                        $string .= $nameOfTopics[$topic] . ' - ';
                        if ($options['condition']['normal'] == $payload){
                            $string .= 'нет протечки'.PHP_EOL;
                        }
                        else {
                            $string .= 'протечка'.PHP_EOL;
                        }
                        continue;
                    }
                    if ($options['format'] === 'check'){
                        continue;
                    }

                    if ($payload === false){
                        $payload = 'memcache no data';
                    }

                    $string .= $nameOfTopics[$topic] . ' - ' . $payload . $topics[$topic]['format'] . PHP_EOL;
                }
            }
            return $string;
        }

        if ($format === "telegram") {
            $string .= 'В холодной прихожке: ' . $this->getCacheMqtt('holl/temperature')
                . ' °C | ' . $this->getCacheMqtt('holl/humidity') . '%' . PHP_EOL;
            $string .= 'В пристройке: ' . $this->getCacheMqtt('margulis/temperature')
                . ' °C | ' . $this->getCacheMqtt('margulis/humidity') . '%' . PHP_EOL;
            $string .= 'В низах: ' . $this->getCacheMqtt('underflor/temperature')
                . ' °C | ' . $this->getCacheMqtt('underflor/humidity') . '%' . PHP_EOL;
            $string .= 'Под низах: ' . $this->getCacheMqtt('underground/temperature')
                . ' °C | ' . $this->getCacheMqtt('underground/humidity') . '%' . PHP_EOL;
            return $string;
        }
        if ($format === "alice") {
            $string .= 'В холодной прихожке: '
                . str_replace('.0','', $this->getCacheMqtt('holl/temperature'))        . ', ';
            $string .= 'В пристройке: '
                . str_replace('.0','', $this->getCacheMqtt('margulis/temperature'))    . ', ';
            $string .= 'В низах: '
                . str_replace('.0','', $this->getCacheMqtt('underflor/temperature'))   . ', ';
            $string .= 'Под низах: '
                . str_replace('.0','', $this->getCacheMqtt('underground/temperature')) . '.'.PHP_EOL;
            return $string;
        }
        return "Необходимо просмотреть функцию генерации ответа!";
    }

    /**
     * Save state modules in memcached
     *
     * @var Mqtt $message
     * @param $optionsTopics
     * @param $message
     */
    public function isOnline($message): void
    {
        $modules = Mqtt::getModuleNames();
        foreach ($modules as $module=>$options) {
            if ($options['check_topic'] === $message->topic) {
                $this->setCacheMqtt($module, time());
            }
        }

    }

    /**
     * Check state modules in memcached
     *
     * @return array
     */
    public function checkOnline()
    {
        $modules = Mqtt::getModuleNames();
        $request = [];
        foreach ($modules as $module=>$options) {
            if ($this->getCacheMqtt($module) > (time()-60)) {
                $request[$module] = 'online';
            }
            else {
                $request[$module] = 'offline';
            }
        }

        return $request;

    }

    /**
     * get register topics and there options
     *
     * @return array
     */
    public static function listTopics(): array
    {
        return [
            'underflor/temperature' => [ // низа температура
                'condition' => [ // пороговые значения min max
                    'min' => 5,
                    'max' => 25,
                ],
                'message' => function($value){
                    return 'критичная температура в низах !!! - ' . $value . '°C';
                }, // сообщение отправляемое в телеграм
                'sensorName' => Mqtt::SENSOR_UNDERFLOR_TEMPERATURE, // из модели mqtt
                'users' => ['decole', 'luda'],
                'format' => '°C',
                'type' => 'sensor',
            ],
            'underflor/humidity' => [ // низа влажность
                'condition' => [
                    'min' => 28,
                    'max' => 80,
                ],
                'message' => function($value){
                    return 'критичная влажность в низах !!! - ' . $value . '%';
                },
                'sensorName' => Mqtt::SENSOR_UNDERFLOR_HUMIDITY,
                'users' => ['decole', 'luda'],
                'format' => '%',
                'type' => 'sensor',
            ],
            'underground/temperature' => [ // под низами температура
                'condition' => [
                    'min' => 5,
                    'max' => 28,
                ],
                'message' => function($value){
                    return 'критичная температура под низами !!! - ' . $value . '°C';
                },
                'sensorName' => Mqtt::SENSOR_UNDERGROUND_TEMPERATURE,
                'users' => ['decole', 'luda'],
                'format' => '°C',
                'type' => 'sensor',
            ],
            'underground/humidity' => [ // под низами влажность
                'condition' => [
                    'min' => 40,
                    'max' => 80,
                ],
                'message' => function($value){
                    return 'критичная влажность под низами !!! - ' . $value . '%';
                },
                'sensorName' => Mqtt::SENSOR_UNDERGROUND_HUMIDITY,
                'users' => ['decole', 'luda'],
                'format' => '%',
                'type' => 'sensor',
            ],
            'holl/temperature' => [ // холодная прихожка температура
                'condition' => [
                    'min' => 8,
                    'max' => 35,
                ],
                'message' => function($value){
                    return 'критичная температура в холодной прихожке !!! - ' . $value . '°C';
                },
                'sensorName' => Mqtt::SENSOR_HOLL_TEMPERATURE,
                'users' => ['decole', 'luda'],
                'format' => '°C',
                'type' => 'sensor',
            ],
            'holl/humidity' => [ // холодная прихожка влажность
                'condition' => [
                    'min' => 10,
                    'max' => 88,
                ],
                'message' => function($value){
                    return 'критичная влажность в холодной прихожке !!! - ' . $value . '%';
                },
                'sensorName' => Mqtt::SENSOR_HOLL_HUMIDITY,
                'users' => ['decole', 'luda'],
                'format' => '%',
                'type' => 'sensor',
            ],
            'margulis/temperature' => [ // пристройка температура
                'condition' => [
                    'min' => 15,
                    'max' => 35,
                ],
                'message' => function($value){
                    return 'критичная температура в пристройке !!! - ' . $value . '°C';
                },
                'sensorName' => Mqtt::SENSOR_MARGULIS_TEMPERATURE,
                'users' => ['decole', 'luda'],
                'format' => '°C',
                'type' => 'sensor',
            ],
            'margulis/humidity' => [ // пристройка влажность
                'condition' => [
                    'min' => 10,
                    'max' => 80,
                ],
                'message' => function($value){
                    return 'критичная влажность в пристройке !!! - ' . $value . '%';
                },
                'sensorName' => Mqtt::SENSOR_MARGULIS_HUMIDITY,
                'users' => ['decole', 'luda'],
                'format' => '%',
                'type' => 'sensor',
            ],
            // sensor from smart watering
            'water/leakage' => [ // датчик протечки воды
                'condition' => [
                    'normal'  => 0,
                    'warning' => 1,
                ],
                'message' => function($value){
                    return 'Протечка клапанов полива!';
                },
                'sensorName' => Mqtt::SENSOR_WATER_LEAKAGE,
                'users' => ['decole', 'luda'],
                'linked' => 'watering', // привязано к автополиву и WateringLogic
                'format' => 'leakage',
                'type' => 'sensor',
            ],
            // state of watering swifts
            'water/check/major' => [ // датчик протечки воды
                'condition' => [
                    'on'  => 1,
                    'off' => 0,
                ],
                'message' => function($value){
                    return 'Состояние главного клапана неизвестно!';
                },
                'sensorName' => Mqtt::SENSOR_WATER_LEAKAGE,
                'users' => ['decole', 'luda'],
                'linked' => 'watering', // привязано к автополиву и WateringLogic
                'format' => 'check',
                'type' => 'sensor',
            ],
            'water/check/1' => [ // датчик протечки воды
                'condition' => [
                    'normal'  => 0, // 1 - датчик = нет протечки
                    'warning' => 1,
                ],
                'message' => function($value){
                    return 'Состояние клапана №1 неизвестно!';
                },
                'sensorName' => Mqtt::SENSOR_WATER_LEAKAGE,
                'users' => ['decole', 'luda'],
                'linked' => 'watering', // привязано к автополиву и WateringLogic
                'format' => 'check',
                'type' => 'sensor',
            ],
            'water/check/2' => [ // датчик протечки воды
                'condition' => [
                    'normal'  => 0, // 1 - датчик = нет протечки
                    'warning' => 1,
                ],
                'message' => function($value){
                    return 'Состояние клапана №2 неизвестно!';
                },
                'sensorName' => Mqtt::SENSOR_WATER_LEAKAGE,
                'users' => ['decole', 'luda'],
                'linked' => 'watering', // привязано к автополиву и WateringLogic
                'format' => 'check',
                'type' => 'sensor',
            ],
            'water/check/3' => [ // датчик протечки воды
                'condition' => [
                    'normal'  => 0, // 1 - датчик = нет протечки
                    'warning' => 1,
                ],
                'message' => function($value){
                    return 'Состояние клапана №3 неизвестно!';
                },
                'sensorName' => Mqtt::SENSOR_WATER_LEAKAGE,
                'users' => ['decole', 'luda'],
                'linked' => 'watering', // привязано к автополиву и WateringLogic
                'format' => 'check',
                'type' => 'sensor',
            ],

            /**
             * active public topics (relays/swifts)
             */

            'water/major' => [ // главный клапан полива
                'condition' => [
                    'on' => '1',
                    'off' => '0',
                ],
                'message' => function($value){
                    return 'главный клапан полива - ' . $value;
                },
                'sensorName' => Mqtt::SWIFT_WATER_MAJOR,
                'users' => ['decole', 'luda'],
                'format' => '',
                'arduionIotId' => 1,
                'checkTopic' => 'water/check/major', // needed for check state in agent/check-commands
                'type' => 'swift',
            ],
            'water/1' => [ // клапан 1 полива
                'condition' => [
                    'on' => '1',
                    'off' => '0',
                ],
                'message' => function($value){
                    return 'клапан 1 полива - ' . $value;
                },
                'sensorName' => Mqtt::SWIFT_WATER_1,
                'users' => ['decole', 'luda'],
                'format' => '',
                'arduionIotId' => 2,
                'checkTopic' => 'water/check/1', // needed for check state in agent/check-commands
                'type' => 'swift',
            ],
            'water/2' => [ // клапан 2 полива
                'condition' => [
                    'on' => '1',
                    'off' => '0',
                ],
                'message' => function($value){
                    return 'клапан 2 полива - ' . $value;
                },
                'sensorName' => Mqtt::SWIFT_WATER_2,
                'users' => ['decole', 'luda'],
                'format' => '',
                'arduionIotId' => 3,
                'checkTopic' => 'water/check/2', // needed for check state in agent/check-commands
                'type' => 'swift',
            ],
            'water/3' => [ // клапан 3 полива
                'condition' => [
                    'on' => '1',
                    'off' => '0',
                ],
                'message' => function($value){
                    return 'клапан 3 полива - ' . $value;
                },
                'sensorName' => Mqtt::SWIFT_WATER_3,
                'users' => ['decole', 'luda'],
                'format' => '',
                'arduionIotId' => 4,
                'checkTopic' => 'water/check/3', // needed for check state in agent/check-commands
                'type' => 'swift',
            ],
        ];
    }

}
