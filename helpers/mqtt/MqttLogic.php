<?php
namespace app\helpers\mqtt;

//use api\base\Object;
use app\helpers\telegram\TelegramLogic;
use app\models\Arduinoiot;
use app\models\Mqtt;
use Yii;
use yii\base\BaseObject;



/**
 * This class make logic on site from command class Mqtt
 */
class MqttLogic extends BaseObject
{
    public $host = 'localhost';
    public $port = 1883;
    public $time = 60;
    private $client;
    private $isConnect = false;
    private $alarmTemper = 43;
    private $periodicTime = 1800; // период произведения анализа в методе pprocess

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

    public function disconnect()
    {
        if($this->isConnect){
            $this->client->disconnect();
        }
    }

    /**
     * @param $message
     * @throws \yii\base\InvalidConfigException
     *
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
            // save in memecache topic and payload
            $this->setCacheMqtt($message->topic, $message->payload);
            //echo 'set cache: topic ' . $message->topic . ', payload ' . $message->payload . PHP_EOL;
            // if send changing command in mqtt mobile app
            if($options['type'] === 'swift') {
                $this->changeState($options, $message);
            }
            if ($options['type'] === 'sensor') {
                $this->checkFire($options, $message);
            }
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
        $data  = $cache->get($key);
        if ($data === false) {
            $data = null;
            $cache->set($key, $data);
        }

        return $data;

    }

    /**
     * Set cache to memcache
     *
     * @param $key
     * @param $value
     */
    private function setCacheMqtt($key, $value)
    {
        $cache = Yii::$app->cache;
        $cache->set($key, $value);
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
            if ($payload !== null && $option['type'] === 'sensor' &&
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
     * checking sensors to anomaly payload
     *
     * @param $options
     * @param $message
     */
    private function changeState($options, $message): void
    {
        if (isset($options['condition']['on'], $options['condition']['off']) ) {
            if (($options['condition'][$message->payload] == 0) || ($options['condition'][$message->payload] == 1)) {
                // safe new state swift
                $customer = Arduinoiot::find()->where(['id' => $options['arduionIotId']])->limit(1)->one();
                $customer->relay1 = $options['condition'][$message->payload];
                $customer->save();

                $this->mailing($options['message']($message->payload), $options);
            }
            else {
                $this->mailing('Ошибка '.$message->topic.' - прислал плохое значение'.$message->payload, $options);
            }
        }

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
                $customer = new Mqtt();
                $customer->topic = $topic;
                $customer->payload = $payload;
                $customer->datetime = date('Y-m-d H:i:s');
                //Yii::$app->formatter->asDate(date('Y-m-d H:i:s'), 'yyyy-MM-dd HH:mm:ss');
                if($cache->get($topic) !== null) {
                    if (!$customer->save()) {
                        echo "not added payload \n";
                        var_dump('topic:' . $topic . ', payload:' . $payload);
                    }
                }
            }
        }

    }

    /**
     * sending specific massage to telegram from needed users, inserting in options['users']
     *
     * @param $massage
     * @param $options
     */
    private function mailing($massage, $options): void
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
                    'min' => 20,
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
                    'max' => 25,
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
                    'min' => 20,
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
                    'min' => 5,
                    'max' => 30,
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
                    'min' => 20,
                    'max' => 80,
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
                    'min' => 5,
                    'max' => 28,
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
                    'min' => 20,
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

            /**
             * active public topics
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
                'type' => 'swift',
            ],
            'noname' => [ // не идентифицированное устройство
                'condition' => [
                    'on' => '1',
                    'off' => '0',
                ],
                'message' => function($value){
                    return 'обнаружен не идентифицированный переключатель - ' . $value;
                },
                'sensorName' => Mqtt::SWIFT_DEFAULT,
                'users' => ['decole', 'luda'],
                'format' => '',
                'arduionIotId' => null,
                'type' => 'swift',
            ],
        ];
    }

}