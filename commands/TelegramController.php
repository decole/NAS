<?php

namespace app\commands;

use api\base\API;
use api\response\Error;
use api\response\Update;
use api\response\Message;
use app\helpers\telegram\TelegramLogic;
use app\models\Arduino;
use app\models\SimpleData;
use app\models\Weather;
use DateTime;
use yii\base\Module;
use yii\console\Controller;
use api\keyboard\ReplyKeyboardMarkup;
use api\keyboard\button\KeyboardButton;

class TelegramController extends Controller
{
    /**
     * Telegram bot for Home Automation
     *
     * debug functions to insert automation
     * check errors from sensors
     * in one time send specific messages to specific user(s)
     *
     */

    /**
     * @var TelegramLogic
     */
    private $api;


    public function __construct(string $id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->api = new TelegramLogic();
    }

    public function actionIndex()
    {
        /** @var Weather $acuw */
        $string = 'Сейчас ' . $this->getRusDay() . date(" d ") . $this->getRusMonth() . PHP_EOL;
        $acuweth = $this->getAcuweather();
        $string .= 'Температура ' . $acuweth->temperature . ' С`- ' . $acuweth->spec;
        $this->actionSend($string);
    }

    /**
     * Send message in Telegram
     * @param $text string
     * @param string $user
     * @return mixed
     */
    public function actionSend($text, $user = 'decole')
    {
        if($this->api->sendByUser($text, $user)) {
            $this->stdout('message send' . PHP_EOL);
        } else {
            $this->stderr('message not send' . PHP_EOL);
        }
    }

    protected function getRusDay()
    {
        $day = date("l");

        $mass['Monday']    = 'Понедельник';
        $mass['Tuesday']   = 'Вторник';
        $mass['Wednesday'] = 'Среда';
        $mass['Thursday']  = 'Четверг';
        $mass['Friday']    = 'Пятница';
        $mass['Saturday']  = 'Суббота';
        $mass['Sunday']    = 'Воскресенье';

        $isDay = str_replace($day, $mass[$day], $day);

        return $isDay;

    }

    protected function getRusMonth()
    {
        $month = date("F");

        $mass['January'] = 'Января';
        $mass['February'] = 'Февраля';
        $mass['March'] = 'Марта';
        $mass['April'] = 'Апреля';
        $mass['May'] = 'Мая';
        $mass['June'] = 'Июня';
        $mass['July'] = 'Июля';
        $mass['August'] = 'Августа';
        $mass['September'] = 'Сентября';
        $mass['October'] = 'Октября';
        $mass['November'] = 'Ноября';
        $mass['December'] = 'Декабря';

        $isMonth = str_replace($month, $mass[$month], $month);

        return $isMonth;

    }

    /**
     * get AcuWeather parsed information at now
     */
    public function getAcuweather()
    {
        return Weather::find()->orderBy(['date' => SORT_DESC])->one();
    }

}