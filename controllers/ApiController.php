<?php

namespace app\controllers;

use app\helpers\mqtt\MqttLogic;
use app\models\Arduino;
use app\models\Arduinoiot;
use app\models\Mqtt;
use app\models\Weather;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\ContentNegotiator;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class ApiController for using all WEB API actions
 * for Arduino iot devices at home
 * for GUI www.uberserver.ru
 * @package app\controllers
 */
class ApiController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
//					'application/xml' => Response::FORMAT_XML,
                ],
            ],
            'verbFilter' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'get' => ['get'],
//                    'relay' => ['get'],
//					'create' => ['get', 'post'],
//					'update' => ['get', 'put', 'post'],
//					'delete' => ['post', 'delete'],
                ],
            ],
            'authenticator' => [
                'class' => CompositeAuth::class,
            ],
            'rateLimiter' => [
                'class' => RateLimiter::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * !!! Deprecated - is function for old version sensors
     *
     * Displays page from post data Arduino from server.
     * transmit data to Arduino
     * return nothing, add data on BD
     * @return null
     */
    public function actionIndex()
    {
        return null;
    }

    /**
     * uberserver.ru/relay 'GET' query  for update status relays.
     *
     * @return int|null|string|bool
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function actionRelay(): bool
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        // http://uberserver.ru/api/relay?a=1&r=0
        if (Yii::$app->request->isGet) {
            $param = Yii::$app->request->get();
            $param['a'] = $param['a'] ?? null; // number of sensor
            $param['r'] = $param['r'] ?? null; // status relay  0 - off, 1 - on

            if($param['a'] !== null && $param['r'] !== null){
                /** @var Arduinoiot $model */
                $model = Arduinoiot::find()
                    ->where(['id' => $param['a']])
                    ->one();
                $model->relay1 = $param['r'];
                $model->save();
                // send to mqtt tpic - relay - on
                $this->changeRelay($param['a'],$param['r']);

                return $model->relay1;

            }

            echo var_dump($param, true);

        }

        return null;
    }

    /**
     * generate charts
     * may in /site/chart
     * @param $date
     * @param $topic
     * @return array
     */
    public function actionChart($date, $topic): array
    {
        if($date == 'current') {
            $date = date('Y-m-d');
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        $mqttData = Mqtt::find()->where(['DATE(`datetime`)' => $date, 'topic' => $topic])->all();
        $weatherData = Weather::find()->where(['DATE(`date`)' => $date])->all();
        $mqttData = ArrayHelper::toArray($mqttData);
        $weatherData = ArrayHelper::toArray($weatherData);

        $chart = [];
        $min = '';
        foreach ($mqttData as $mqtt) {
            $timeMqtt = date_timestamp_get(date_create($mqtt['datetime']));
            foreach ($weatherData as $key => $acuweather) {
                $timeAcuweather = date_timestamp_get(date_create($acuweather['date']));
                if($timeMqtt > $timeAcuweather ) {
                    $min = $acuweather['temperature'];
                }
                if($timeMqtt < $timeAcuweather) {
                    if(empty($min)) {
                        $min =  $acuweather['temperature'];
                    }
                    $chart[$mqtt['datetime']] = [
                        'mqtt' => $mqtt['payload'],
                        'acuweather' => $acuweather['temperature'],
                    ];
                    break;
                }
            }
        }
        $template = [];

        $mqttValues = [];
        $weatherValues = [];
        foreach ($chart as $valueChart) {
            $mqttValues[] = $valueChart['mqtt'];
            $weatherValues[] = $valueChart['acuweather'];
        }

        $template['labels'] = array_keys($chart); // ["$topic"]
        $template['datasets'] = [
            [
                'data' => array_values($mqttValues),
                'label' => 'Mqtt sensor',
                'fill'=>false,
                'borderColor'=>'rgb(75, 192, 192)',
                'lineTension'=>0.1,
            ],
            [
                'data' => array_values($weatherValues),
                'label' => 'AcuWeather',
                'fill'=>false,
                'borderColor'=>'rgb(114, 151, 151)',
                'lineTension'=>0.1,
            ],
        ];

        return $template;
    }

    /**
     * Validate leakage state in smart watering
     * @param $topic
     * @return void
     */
    public function actionLeakage($topic): void
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        $mqtt = new MqttLogic();
        $options = $mqtt::listTopics();
        $stateLeakage = $mqtt->getCacheMqtt($topic);
        if($options[$topic]['condition']['normal'] == $stateLeakage)
        {
            echo 0;
        }
        else {
            echo 1;
        }

    }

    /**
     * Show state Emergency Stop topic - water/alarm and manipulate from here
     * @param $action
     * @param $topic
     * @return void
     */
    public function actionEmergencyStop($action, $topic): void
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        if($action !== null && $topic !== null) {
            $mqtt = new MqttLogic();
            if($action === 'on') {
                $mqtt->post($topic, 1);
                echo 1;
            }
            if($action === 'off') {
                $mqtt->post($topic, 0);
                echo 0;
            }
            if($action === 'state') {
                echo $mqtt->getCacheMqtt($topic);
            }
        }
        else {
            echo 0;
        }

    }

    public function actionRelayState($topic): void
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        if( $topic !== null) {
            $mqtt = new MqttLogic();
            $options = $mqtt::listTopics();
            foreach ($options as $swift=>$option) {
                if($option['type'] === 'swift' && $option['arduionIotId'] == $topic) {
                    echo $mqtt->getCacheMqtt($swift);
                }
            }
        }
    }

    public function actionSensorState($topic): void
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        if( $topic !== null) {
            $mqtt = new MqttLogic();
            $options = $mqtt::listTopics();
            foreach ($options as $sensor=>$option) {
                if($option['type'] === 'sensor' && $sensor == $topic) {
                    echo $mqtt->getCacheMqtt($sensor);
                }
            }
        }
    }

    /**
     * verify name of relay to Arduino sensor
     *
     * @param $topic
     * @param $data
     * @return int|mixed|\yii\console\Response
     */
    private function changeRelay($topic, $data): void
    {
        if($this->nameRelay($topic)){
            $topic = $this->nameRelay($topic);
            //$data = $this->status($data); // 0 change to off, 1 change to on
            $mqtt = new MqttLogic;
            $mqtt->post($topic, $data);
            $mqtt->disconnect();
        }

    }

    /**
     * put name for real topics posting in mqtt protocol
     * @param $name
     * @return string|bool
     */
    private function nameRelay($name):string
    {
        $topics = Mqtt::getSwiftNames();
        $finalArray = [];
        $i = 1;
        foreach ($topics as $key=>$value){
            if($key === 'noname'){
                continue;
            }
            $finalArray[$i] = $key;
            $i++;
        }

        if($finalArray[$name]){
            return $finalArray[$name];
        }
        return false;

    }

    /**
     * Token is number of Arduino
     * @param $token
     * @return int
     */
    public static function status($token)
    {
        $status = 'off';
        switch ($token) {
            case 0:
                $status = 'off';
                break;
            case 1:
                $status = 'on';
                break;
        }

        return $status;

    }

}
