<?php

namespace app\controllers;

use app\helpers\mqtt\MqttLogic;
use app\helpers\telegram\TelegramLogic;
use app\models\Alice;
use app\models\AliceSecure;
use app\models\Arduino;
use app\models\Arduinoiot;
use app\models\Mqtt;
use app\models\Schedule;
use app\models\Weather;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\ContentNegotiator;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class AliceController for using all WEB API actions from Alice
 *
 * @package app\controllers
 */

class AliceController extends Controller
{
    public $content;
    public $enableCsrfValidation = false;

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
                ],
            ],
            'verbFilter' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get', 'post'],
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
     * Speak to Yandex Alice
     *
     * @return array
     */
    public function actionIndex(): array
    {
        $apiRequestArray = json_decode(trim(file_get_contents('php://input')), true);
        $text = $this->process($apiRequestArray);
        $arrayToEncode = [
            "response" => [
                "text" => htmlspecialchars_decode($text),
                "end_session" => false,
            ],
            "session" => [
                "session_id" => $apiRequestArray['session']['session_id'],
                "message_id" => $apiRequestArray['session']['message_id'],
                "user_id" => $apiRequestArray['session']['user_id'],
            ],
            "version" => $apiRequestArray['version']
        ];

        return $arrayToEncode;
    }

    /**
     * обработчик присылаемых команд
     *
     * @param $apiRequestArray
     * @return string
     */
    private function process($apiRequestArray): ?string
    {
        if($this->secure($apiRequestArray) === false){
            return 'Вы не зарегистрированный пользователь.';
        }


        $tokens = $apiRequestArray['request']['nlu']['tokens'];
        $commands = [
            'привет'  => 'hello',
            'ping'    => 'ping',

            'статус'  => 'state',

            'авария'  => 'alarmOn',

            'сенсоры' => 'state',
            'сенсор'  => 'state',
            'полив'   => 'state',
            'клапаны' => 'state',
            'клапан'  => 'state',

            'планировщик' => 'scheduler',

            'погода'  => 'weather',

            'шланг'   => 'hose',

            //'самоуничтожение' => 'destroy', // уничтожить данные и заморозить сервисы, отключить всю электронику
            'заблокировать' => 'blocking',  // заблокировать всех пользователей, на случай взлома
            //'резервная' => 'backup',        // резервная копия базы
        ];

        foreach ($commands as $key=>$value) {
            if(in_array($key, $tokens)) {
                $tokens = $this->delete_value_in_array($key, $tokens);
                return $this->$value($tokens);
            }
        }


        return $this->error();

    }

    /**
     * Приветствие
     *
     * @param $text
     * @return string
     */
    private function hello($text): string
    {
        return 'Привет, я уберсервер, я обслуживаю этот дом. Благодаря мне он становится умным.Мне можно сказать: 
        Привет, статус сенсоров, статус полива, общий статус, запусти планировщик, стоп или авария для аварийного 
        выключения автополива. Включи шланг.';

    }

    /**
     * Яндекс раз в пол минуты пингует навык, сцуко. Для уменьшения нагрузки на серв сделал специальную заглушку
     *
     * @param $text
     * @return string
     */
    private function ping($text): string
    {
        return 'pong';

    }

    /**
     * Статус сенсоров и полива
     *
     * @param $text
     * @return string
     */
    private function state($tokens): string
    {
        $alice = new Alice();
        if(
            in_array('сенсор', $tokens) ||
            in_array('сенсоры', $tokens) ||
            in_array('сенсоров', $tokens) ||
            in_array('датчик', $tokens) ||
            in_array('датчики', $tokens) ||
            in_array('датчиков', $tokens)
        ) {

            return $alice->stateSensors();
        }

        if(
            in_array('полив', $tokens) ||
            in_array('полива', $tokens) ||
            in_array('клапан', $tokens) ||
            in_array('клапаны', $tokens) ||
            in_array('клапанов', $tokens)
        ) {
            return $alice->stateSmartWatering();
        }

        if( in_array('общий', $tokens) ) {
            return $alice->stateAll();
        }

        return 'Скажи "Статус сенсоров" или "Статус полива"?';

    }

    /**
     * Планировщик задач, сейчас это только автополив (вызов - для активации задания)
     *
     * @param $tokens
     * @return string
     */
    private function scheduler($tokens): string
    {
        $alice = new Alice();
        if(
            in_array('запуск', $tokens) ||
            in_array('старт', $tokens) ||
            in_array('запустить', $tokens) ||
            in_array('стартовать', $tokens)
        ) {
            return $alice->startScheduleWatering();
        }

        if(
            in_array('стоп', $tokens) ||
            in_array('останови', $tokens) ||
            in_array('останов', $tokens)
        ) {
            return $alice->stopScheduleWatering();
        }

        return 'Уточните, сказав: "Планировщик старт" или "Планировщик стоп"';

    }

    /**
     * Аварийный останов полива
     *
     * @param $text
     * @return string
     */
    private function alarmOn($text): string
    {
        $alice = new Alice();
        return $alice->alarmOn();

    }

    /**
     * Вывод погоды из базы данных
     *
     * @param $text
     * @return string
     */
    private function weather($text): string
    {
        $acuweth = Weather::find()->orderBy(['date' => SORT_DESC])->one();
        return 'Температура ' . $acuweth->temperature . ' С`- ' . $acuweth->spec;

    }

    /**
     * Активация шланга - муфта со шлангом подсоединена к главному клапану, при его активации появится давление в ветке
     * со шлангом
     *
     * @param $text
     * @return string
     */
    private function hose($tokens): string
    {
        $alice = new Alice();
        if(
            in_array('запуск', $tokens) ||
            in_array('старт', $tokens) ||
            in_array('запустить', $tokens) ||
            in_array('стартовать', $tokens)
        ) {
            return $alice->hoseOn();
        }

        if(
            in_array('стоп', $tokens) ||
            in_array('останови', $tokens) ||
            in_array('останов', $tokens) ||
            in_array('выключить', $tokens) ||
            in_array('выключи', $tokens)
        ) {
            return $alice->hoseOff();
        }

        return $alice->hoseOn();

    }

    /**
     * При нераспознаной команде вывести фразу "Ошибка.
     *
     * @param $text
     * @return string
     */
    private function error(): string
    {
        return 'Ошибка';
    }

    /**
     * Удаляет значение из массива
     *
     * @param $value
     * @param $array
     * @return mixed
     */
    private function delete_value_in_array($value, $array)
    {
        if ( (($key = array_search($value, $array)) !== false) && (count($array) > 1) ) {
            unset($array[$key]);
        }

        return $array;

    }

    /**
     * Сохранение всех диалогов и проверка на зарегистрированных пользователей
     *
     * @param $apiRequestArray
     * @var Alice $assistant
     * @return bool
     */
    private function secure($apiRequestArray)
    {
        if(
            $apiRequestArray['request']['command'] == 'ping' ||
            $apiRequestArray['request']['command'] == null
        ) {
            return true;
        }

        $assistant = new Alice();
        $assistant->saveDialog($apiRequestArray);

        $tokens = $apiRequestArray['request']['nlu']['tokens'];
        $userId = var_export($apiRequestArray['session']['user_id'], true);
        if(
            in_array('регистрация', $tokens) ||
            in_array('зарегистрировать', $tokens)
        ) {
            $security = new AliceSecure();
            $security->registerUser($userId);
            return true;
        }

        return AliceSecure::validateUser($userId);

    }

    /**
     * Уничтожение данных и отключение всех сенсоров
     *
     * @param $apiRequestArray
     * @return string
     */
    private function destroy($apiRequestArray): string
    {
        $aliceSecure = new AliceSecure();
        $aliceSecure->destroy();
        return 'destroy - is set...';

    }

    /**
     * Заблокировать всех зарегистрированных пользователей
     *
     * @param $apiRequestArray
     * @return string
     */
    private function blocking($apiRequestArray): string
    {
        $aliceSecure = new AliceSecure();
        $aliceSecure->blocking();
        return 'blocking - ok';

    }

    /**
     * Команда для создания резерной копии проекта
     *
     * @param $apiRequestArray
     * @return string
     */
    private function backup($apiRequestArray): string
    {
        $aliceSecure = new AliceSecure();
        $aliceSecure->backup();
        return 'backup - ok';

    }

}