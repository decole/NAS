<?php

namespace app\controllers;

use app\helpers\mqtt\MqttLogic;
use app\models\Arduino;
use app\models\Arduinoiot;
use app\models\Mqtt;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\ContentNegotiator;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class ApiController for usign all WEB API actions
 * for Arduino iot devices at home
 * for GUI www.uberserver.ru
 * for Alisa voice
 * @package app\controllers
 */
class ApiController extends Controller
{
    /**
     * @inheritdoc
     */
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
//        Yii::$app->response->format = Response::FORMAT_HTML;
//        if (Yii::$app->request->isGet) {
//            $param = Yii::$app->request->get();
//            // http://192.168.1.5/api/index?token=EB6DD&t=23&h=35&p=
//            // Array ( [token] => EB6DD [t] => 23 [h] => 35 [p] =>  )
//            $param['token'] = $param['token'] ?? 0;
//            $param['t'] = $param['t'] ?? 99;
//            $param['h'] = $param['h'] ?? 0;
//            $param['p'] = $param['p'] ?? '';
//            /** @var integer $nameArduino */
//
//            if( $param['t'] == 99 ) return null;
//            $nameArduino = $this->nameArduino($param['token']);
//
//            $model = new Arduino();
//            $model->load(Yii::$app->request->get());
//            $model->arduino = $nameArduino;
//            $model->temperaturу = intval($param['t']);
//            $model->humidity = intval($param['h']);
//            $model->pressure = intval($param['p']);
////            $model->date = Yii::$app->formatter->asDate(date("Y-m-d H:i:s"), 'yyyy-MM-dd HH:mm:ss');
//            $model->date = date("Y-m-d H:i:s");
//            //->modify('+3 hour')
//            $model->save();
//
//            $file = "logGetParams.txt";
//            $getParam = var_export($_REQUEST, true).PHP_EOL;
//            file_put_contents($file, $getParam, FILE_APPEND | LOCK_EX);
//        }
        return null;
    }

    /**
     * uberserver.ru/relay 'GET' query  for update status relays.
     *
     * @return int|null|string|void
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function actionRelay()
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
     * verify name of relay to Arduino sensor
     *
     * @param $topic
     * @param $data
     * @return int|mixed|\yii\console\Response
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    private function changeRelay($topic, $data): void
    {
        if($this->nameRelay($topic)){
            $topic = $this->nameRelay($topic);
            $data = $this->status($data);
            $mqtt = new MqttLogic;
            $mqtt->post($topic, $data);
            $mqtt->disconnect();
        }

    }

    /**
     * put name for real topics posting in mqtt protocol
     * @param $name
     * @return string
     */
    private function nameRelay($name)
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
    public function status($token)
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
