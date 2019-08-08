<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;
use app\commands\TelegramController;
use app\helpers\mqtt\MqttLogic;
use app\models\Mqtt;
use DateTime;
use Yii;
use yii\console\Controller;
use yii\db\ActiveRecord;
use yii\db\Expression;


/**
 * Commands for MQTT sensors and posting to MQTT protocol
 */
ini_set('output_buffering','on');

class MqttController extends Controller {
	/**
	 * This contreoller produce MQTT protocol to App
	 */

    /**
     * @param $c - PHP library mosquito-php.so
     */
    public function actionIndex() {
        $mqtt = new MqttLogic();
        $mqtt->listen();
	}

    /**
     * цикл уборки БД
     */
    public function actionTrashbox() {
        /*
         * произвести отбор уникальных записей с дискретизацией в 1 день
         * вытащить id записей, которые больше 1 месяца и поместить их дамп ***.sql и удалить из бд данные записи
         */
//        Mqtt::findAll(new Expression('DATE(time) = :current_date', [':current_date' => date('Y-m-d')]));
        $after = $this->AfterDate(date('m'));
        $afterMonthDays = $after['days'];
        $afterMonth = $after['month'];
        $afterYear = $after['year'];
        $deletingIds = [];
        $afterMonth++; // проверяем этот месяц
        $topics = [
            'underflor/temperature',
            'underflor/humidity',
            'underground/temperature',
            'underground/humidity',
            'holl/temperature',
            'holl/humidity',
            'margulis/temperature',
            'margulis/humidity',
        ];

        for ($i = 1; $i <= $afterMonthDays; $i++) {
            foreach ($topics as $topic) {
                $deletingIds = $this->deleteDubleRecord($topic, "$afterYear-$afterMonth-$i");
            }
//            echo 'work is end '. "$afterYear-$afterMonth-$i" . PHP_EOL;
        }

        return true;

    }

    /**
     * Sending data to topic on mqtt protocol
     * @param $topic $data
     * @return string
     */
    public function actionPost($topic, $data)
    {
        $mqtt = new MqttLogic;
        return $mqtt->post($topic, $data);
    }

    /**
     * Checking offline sensors and sending in Telegram
     */
    public function actionCheckOnline()
    {
        $mqtt = new MqttLogic();
        $check = $mqtt->checkOnline();
        $options = $mqtt::listTopics();
        $message = null;

        foreach($check as $topic=>$value) {
            if($value === 'offline') { // offline
                $message .= $topic.' - is offline'.PHP_EOL;
            }
        }
        if($message !== null){
            $mqtt->mailing($message, $options);
        }

    }

    /**
     * Deleting third month statistics
     * @throws \Exception
     */
    public function actionDeleteOldData(){
        /*
        $date = new \DateTime();
        $start = $date->sub(new \DateInterval('P3M'));
        $start = $start->setDate($start->format('Y'), $start->format('m'), 1)->setTime(0, 0, 0);
        $end = $start->add(new \DateInterval('P1M'));
        Mqtt::deleteAll(['AND', ['>=', 'datetime', $start], ['<', 'datetime', $end]]);
        */
        /*
         * - удалить весь месяц (с первого по последнее число), за третий месяц назад от текущего.
         * например сегодня 20 ноября, удаляются данные 1-31 августа
        */
        // $datas = Mqtt::find()->where(['topic' => $topic, 'DATE(`datetime`)' => $date])->all();
        $afterSecondMonth = $this->AfterDate(date('m'))['month'];
        $afterTherdMonth = $this->AfterDate($afterSecondMonth)['month'];

        Mqtt::deleteAll(['AND',
            ['<', 'datetime', date('Y-'.$afterSecondMonth.'-01 00:00:00')],
            ['>=', 'datetime', date('Y-'.$afterTherdMonth.'-01 00:00:00')]
//            ['>=', 'datetime', date('2018-'.$afterTherdMonth.'-01 00:00:00')]
        ]);


        return true;
    }

    /**
     * @param $month
     * @return array
     * is take befor month - days in month, befor month, and year
     */
    public function AfterDate($month)
    {
        $year = date('Y');
        if ($month > 1) {
            $month = $month - 1;
        }
        if ($month === 1) {
            $month = 12;
            $year = $year - 1;
        }
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        return ['days' => $days,'month' => $month, 'year' => $year];
    }

    private function deleteDubleRecord($topic,$date = 'null')
    {
        if($date === 'null') {
            $date = date('Y-m-d');
        }
        $datas = Mqtt::find()->where(['topic' => $topic, 'DATE(`datetime`)' => $date])->all();
        $old_date = '';
        $old_payload = '';
        $deletingIds = [];

        foreach ($datas as $data) {
            if(empty($old_date)) {
                $old_date = $data->datetime;
            }
            if(empty($old_payload)) {
                $old_payload = $data->payload;
                continue;
            }
            if( $old_payload == $data->payload ) {
                $deletingIds[] = $data->id;
            }
        }

        $deletingIds = array_merge($deletingIds);

        if(count($deletingIds) > 0) {
//            echo 'deleting ids' . PHP_EOL;
            if(!Mqtt::deleteAll(['id' => $deletingIds])) {
                echo "not deleting array massive on " . $topic. ' in date ' . $date . PHP_EOL;
            }
        }

        return $deletingIds;

    }

}
