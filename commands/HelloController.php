<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\helpers\mqtt\MqttLogic;
use app\helpers\watering\WateringLogic;
use app\models\Arduinoiot;
use app\models\Mqtt;
use app\models\Schedule;
use app\models\Weather;
use DateTime;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world'): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Return datetime now.
     */
    public function actionDate(): void
    {
        echo 'helper yii2 - ' . Yii::$app->formatter->asDate(date('Y-m-d H:i:s'), 'yyyy-MM-dd HH:mm:ss') . ' php- ' . date('Y-m-d H:i:s') . PHP_EOL;
    }

    public function actionListCache():void
    {
        $cache = Yii::$app->cache;
        $options = MqttLogic::listTopics();
        foreach ($options as $topic => $option){
//            if($option['type'] === 'sensor') {
                $payload = $cache->get($topic);
//                $customer = new Mqtt();
//                $customer->topic = $topic;
//                $customer->payload = $payload;
//                $customer->datetime = date('Y-m-d H:i:s');
//                //Yii::$app->formatter->asDate(date('Y-m-d H:i:s'), 'yyyy-MM-dd HH:mm:ss');
//                if($cache->get($topic) !== null) {
//                    if (!$customer->save()) {
//                        echo "not added payload \n";
                        var_dump('topic:' . $topic . ', payload:' . $payload);
//                    }
//                }
//            }
        }
    }

    public function actionOnline()
    {
        $mqtt = new MqttLogic();
        $modules = Mqtt::getModuleNames();
        $request = [];

        foreach ($modules as $module=>$options) {
            if ($mqtt->getCacheMqtt($module) > (time()-60)) {
                $request[$module] = 'online';
            }
            else {
                $request[$module] = 'offline';
            }
        }

        var_dump($request);

    }

    public function actionTest()
    {
        $mqtt = new MqttLogic();
        $check = $mqtt->checkOnline();
        $options = $mqtt::listTopics();
        $message = null;

        print_r($check);
        echo PHP_EOL;

        foreach($check as $topic=>$value) {
            if($value === 'offline') { // offline
                $message .= $topic.' - is offline'.PHP_EOL;
            }
        }
        if($message !== null){
            $mqtt->mailing($message, $options);
        }

    }

    public function getCacheMqtt($key)
    {
        $cache = Yii::$app->cache;
        return $cache->get($key);

    }

    public function actionTimer()
    {
        $waterLogic = new WateringLogic();
        $options = $waterLogic::listTimers();
        $timer = 0;
        foreach ($options as $task=>$parameter) {
            if($parameter['type'] === 'check') {
                $date = date('Y-m-d H:i:s');
                $this->changeTimer($parameter['id_in_db'], $date);
            }
            if($parameter['type'] === 'scenario') {
                if(!empty($parameter['time_at'])) {
                    $timer = 0;
                }
                $this->changeTimer($parameter['id_in_db'], $this->setTimer($timer, $parameter['time_at']));
                $timer += $parameter['working_minutes'];
            }
        }

    }

    private function setTimer($minutes, $timeAt): string
    {
        if(!empty($timeAt)) {
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d ') . $timeAt);
        }
        if(empty($timeAt)) {
            $dateTime = new DateTime();
        }
        $dateTime = new DateTime();
        $now = $dateTime->getTimestamp();
        $dateTime->setTimestamp($now + $minutes*60);
        echo 'timer: ' . $minutes . ' ';
        return $dateTime->format("Y-m-d H:i:s");

    }

    private function changeTimer($id_in_db, $setTimer)
    {
        echo "$id_in_db - $setTimer" . PHP_EOL;

    }
}
