<?php

namespace app\controllers;

use app\models\Arduino;
use app\models\Arduinoiot;
use app\models\ContactForm;
use app\models\LoginForm;
use app\models\Mqtt;
use app\models\SimpleData;
use app\models\Weather;
use InvalidArgumentException;
use Yii;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Class SiteController
 * @package app\controllers
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
//                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => false,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['arduino'],
                        'roles' => ['@'],
                        'allow' => true,
                    ],
//                    [
//                        'actions' => ['news'],
//                        'roles' => ['guest'],
//                        'allow' => false,
//                    ],
                    [
//                        'actions' => ['logout'],
                        'allow' => true,
//                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
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
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        if(Yii::$app->user->isGuest){
            return $this->redirect(Yii::$app->user->loginUrl);
        }
        /** @var array $speach */
        $speech = [
            'Отключен VK Bot (граббинг сообщений группы) более не поддерживается.',
            'Есть Telegram Bot - отправка сообщений.',
            'Подключены Arduino модули - температурные датчики, реле, датчик давления.',
        ];
        /** @var array $act */
        $driving = [
            'ard' => 'Что там с Ардуинкой?',
            'telebot' => 'Телеграм Бот ?',
            'vkbot' => 'VK Bot ?',
        ];
        if (Yii::$app->request->isGet) {
            $dialog = Yii::$app->request->get();
            if(isset($dialog['act'])){
                switch ($dialog['act']){
                    case 'ard':
                        $speech  = [
                            'Сервис Ардуинки собирает данные с контроллеров',
                            'Есть 4 абстрактных кнопки изменяющие статус реле',
                            'Так же есть страница с отображением данных сенсоров.',
                        ];
                        $driving = [
                            'top' => 'на главную',
//                            'stop-all' => 'выключить все реле!',
                        ];
                        break;
//                    case 'stop-all':
//                        $speech  = [
//                            'Передана команда всем реде',
//                            'Ждите идет операция!',
//                        ];
//                        $driving = [
//                            'top' => 'на главную',
//                        ];
//                        break;
                    case 'telebot':
                        $speech  = [
                            'Да, есть сторонняя реализация, переделан на более новый движок бота.',
                            'Работает это чудо для уведомления особых пользователей о разных ситуациях в доме и 
                            на сервере.',
                        ];
                        $driving = [
                            'top' => 'на главную',
                        ];
                        break;
                    case 'vkbot':
                        $speech  = [
                            'Да, есть бот, но он сейчас не активен ввиду того, что популярней и быстрее передать 
                            сообщение по телеграм каналу',
                        ];
                        $driving = [
                            'top' => 'на главную',
                        ];
                        break;
                }
            }
        }

        return $this->render('index', [
            'speech' => $speech,
            'actions'    => $driving,
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        $this->layout = 'login';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->redirect(Yii::$app->user->loginUrl);
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Displays form add data user.
     *
     * @return string
     */
    public function actionForm()
    {
        $model = new SimpleData;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }

        //$db_update = 'none';

        return $this->render('form', [
            'model' => $model,
            //'sqlo'  => $db_update,
        ]);
    }
    public function actionChart()
    {
        $charts = [
          0 => [
              'nameObject' => 'холодная прихожка',
              'nameCart' => '',
              'chart' => [0,1,2,3,4,5,6,7,8,9],
          ],
          1 => [
              'nameObject' => 'пристройка',
              'nameCart' => '',
              'chart' => [0,1,2,3,4,5,6,7,8,9],
          ],
          2 => [
              'nameObject' => 'низа',
              'nameCart' => '',
              'chart' => [0,1,2,3,4,5,6,7,8,9],
          ],
          3 => [
              ''
          ]
        ];
        $labels = ['\'10:00\'', '\'10:30\'', '\'11:00\'', '\'11:30\'', '\'12:00\'', '\'12:30\'', '\'13:00\''];
        return $this->render('charts', [
            'charts'=> $charts,
            'labels' => $labels,
        ]);
    }
    /**
     * penetrate polimorfizm
     */
    public function actionCondrad($class = 1)
    {
        //$weather = new \yii\helpers\WeatherController();
        if ($class == 1) {
            $obj = new \app\helpers\weather\OneHelper();
        } elseif ($class == 2) {
            $obj = new \app\helpers\weather\TwoHelper();
        } else {
            throw new InvalidArgumentException;
        };

        return $this->render('condrad', ['obj' => $obj]);

    }

    /**
     * Arduino page for visible statistics of sensors.
     *
     * @return string
     */
    public function actionArduino()
    {
        // @Todo брать данные с топиков из мэмкэш, если нет в мэмкеш вывести вместо данных - ошибка
        $timeLine   = Mqtt::find()->max('datetime');
        $mqtt_massive = Mqtt::find()->where(['datetime' => "$timeLine"])->all();
        $mqtt_array = ArrayHelper::map($mqtt_massive, 'topic', 'payload');

        $mqtt_underflor_temperature   = $this->verifiMqttData($mqtt_array, 'underflor/temperature');
        $mqtt_underflor_humidity      = $this->verifiMqttData($mqtt_array, 'underflor/humidity');
        $mqtt_underground_temperature = $this->verifiMqttData($mqtt_array, 'underground/temperature');
        $mqtt_underground_humidity    = $this->verifiMqttData($mqtt_array, 'underground/humidity');
        $mqtt_holl_temperature        = $this->verifiMqttData($mqtt_array, 'holl/temperature');
        $mqtt_holl_humidity           = $this->verifiMqttData($mqtt_array, 'holl/humidity');
        $mqtt_margulis_temperature    = $this->verifiMqttData($mqtt_array, 'margulis/temperature');
        $mqtt_margulis_humidity       = $this->verifiMqttData($mqtt_array, 'margulis/humidity');

        $timeLineW   = Weather::find()->max('date');
        $acuweather = Weather::find()->where(['date' => "$timeLineW"])->one();
        // изменить 0 или 1 на 'on' или 'off' в сенсоре c id в ячейке 'relay1', есть еще ячейка 'relay2'
        $relays = Arduinoiot::find()->all();
        // convert data fron view arduino.php
        $ArrayRalays = ArrayHelper::toArray($relays, [
            'app\models\Post' => [
                'id',
                'name',
                'ralay1'
            ],
        ]);

        foreach ($ArrayRalays as $value){
            $massive_ralays["$value[id]"]['relay1'] = ApiController::status($value['relay1']);
            $massive_ralays["$value[id]"]['name'] = $value['name'];
        }

        return $this->render('arduino', [
            'acuweather'=> $acuweather,
            'underflor_temperature'   => $mqtt_underflor_temperature,
            'underflor_humidity'      => $mqtt_underflor_humidity,
            'underground_temperature' => $mqtt_underground_temperature,
            'underground_humidity'    => $mqtt_underground_humidity,
            'holl_temperature'        => $mqtt_holl_temperature,
            'holl_humidity'           => $mqtt_holl_humidity,
            'margulis_temperature'    => $mqtt_margulis_temperature,
            'margulis_humidity'       => $mqtt_margulis_humidity,
            'ralays' => $massive_ralays,
        ]);
    }

    protected function verifiMqttData($array, $topic)
    {
        if(isset($array[$topic])){
            return $array[$topic];
        }

        return Mqtt::find()->where(['topic' => $topic])->orderBy('datetime DESC')->limit(1)->select('payload')->scalar();
    }

    public function pressureToMmRt($pressure)
    {
        if($pressure > 0 && is_int($pressure)){
            $pressure = $pressure*0.0075006375541925;
            return number_format($pressure, 2, '.', '');
        }

        return null;
    }

    private function reversArrayArduino($array)
    {
        $count = count($array);
        $f = $count;
        $new_array = [];
        for($i = 0; $i < $count; $i++) {
            $f--;
            $new_array[$i] = $array[$f];
        }

        return $new_array;
    }


}
