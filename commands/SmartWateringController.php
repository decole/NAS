<?php

namespace app\commands;

use app\helpers\watering\WateringLogic;
use yii\console\Controller;
use yii\base\Module;
use app\helpers\mqtt\MqttLogic;


class SmartWateringController extends Controller
{
    /**
     * Smart Watering commands
     *
     * Взаимодействие через transiver - путем изменения состояния реле в базе данных по расписанию
     * Change state watering swifts in database
     */

    private $mqtt;
    private $options;
    private $water;
    private $nameTopicLeakage = 'water/leakage';

    public function __construct(string $id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->mqtt = new MqttLogic();
        $this->options = $this->mqtt::listTopics();
        $this->water = new WateringLogic();
    }

    public function actionIndex(): void
    {
        echo 'Smart Watering is set!'.PHP_EOL;
    }

    /**
     * Валидация команд по топикам и топикам проверки состояния клапанов
     */
    public function actionCheckCommands(): void
    {
        $mqtt = $this->mqtt;
        $options = $this->options;
        foreach ($options as $topic=>$option) {
            if($option['type'] === 'swift') {
                $stateTopic = $mqtt->getCacheMqtt($topic);
                $checkStateTopic = $mqtt->getCacheMqtt($option['checkTopic']);
                if($stateTopic !== $checkStateTopic) {
                    // если есть разница в состояниях топиков полива, то подождать 3 минуты и после сравнить, и если
                    // разница состояний осталась, между топиком и топиком проверки состояния, то отправить в телеграм
                    $mqtt->checkAlarmTopic($topic, $option['checkTopic']);
                }
                // проверка всех топиков в checkStateTopicAlarm на просроченность (удаление просроченных топиков)
                $mqtt->checkOldMemcachedAlarmTopics();
            }
        }

    }

    /**
     * Проверка на правильность работы центрального клапана.
     */
    public function actionCheckMajor(): void
    {
        $this->water->wateringCheckMajor();

    }

    /**
     * Включение главного клапана
     */
    public function actionMajorOn(): void
    {
        if($this->checkLeakage($this->nameTopicLeakage) == 0) {
            $this->water->MajorOn();
        }

    }

    /**
     * Выключение главного клапана
     * главный клапан отключается последним во всех цыклах
     * !!! так же эта команда отключает все клапаны
     */
    public function actionMajorOff(): void
    {
        $this->water->MajorOff();

    }

    /**
     * Включение клапана 1
     */
    public function actionOneOn(): void
    {
        if($this->checkLeakage($this->nameTopicLeakage) == 0) {
            $this->water->OneOn();
        }

    }

    /**
     * Выключение клапана 1
     */
    public function actionOneOff(): void
    {
        $this->water->OneOff();

    }

    /**
     * Включение клапана 2
     */
    public function actionTwoOn(): void
    {
        $mqtt = new MqttLogic();
        $options = $mqtt::listTopics();
        if($this->checkLeakage($this->nameTopicLeakage) == 0) {
            $this->water->TwoOn();
        }

    }

    /**
     * Включение клапана 2
     */
    public function actionTwoOff(): void
    {
        $this->water->TwoOff();

    }

    /**
     * Включение клапана 3
     */
    public function actionThreeOn(): void
    {
        $mqtt = new MqttLogic();
        $options = $mqtt::listTopics();
        if($this->checkLeakage($this->nameTopicLeakage) == 0) {
            $this->water->ThreeOn();
        }

    }

    /**
     * Включение клапана 3
     */
    public function actionThreeOff(): void
    {
        $this->water->ThreeOff();

    }

    /**
     * Аварийный останов всех клапанов, пользоваться в крайнем случае, для консольных команд
     */
    public function actionAlarm(): void
    {
        $this->water->AlarmOn();

    }

    /**
     * for testing in memcached topic `checkStateTopicAlarm`
     */
    public function actionListCache()
    {
        $mqtt = new MqttLogic();
        $cache = $mqtt->getCacheMqtt('checkStateTopicAlarm');
        print_r($cache);
        echo PHP_EOL;

    }

    private function checkLeakage($leakage)
    {
        $mqtt = $this->mqtt;
        $options = $this->options;
        return $mqtt->getCacheMqtt($leakage) === $options[$leakage]['condition']['normal'];

    }

}
