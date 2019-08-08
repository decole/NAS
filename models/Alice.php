<?php

namespace app\models;

use app\helpers\mqtt\MqttLogic;
use app\helpers\watering\WateringLogic;
use Yii;

/**
 * This is the model class for table "alice".
 *
 * @property int $id
 * @property string $session_id
 * @property string $user_id
 * @property string $command
 * @property string $tokens
 * @property string $json
 * @property string $create_date
 */
class Alice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'alice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['session_id', 'user_id', 'command', 'tokens', 'json'], 'required'],
            [['json'], 'string'],
            [['create_date'], 'safe'],
            [['session_id'], 'string', 'max' => 40],
            [['user_id'], 'string', 'max' => 80],
            [['command', 'tokens'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'session_id' => 'Session ID',
            'user_id' => 'User ID',
            'command' => 'Command',
            'tokens' => 'Tokens',
            'json' => 'Json',
            'create_date' => 'Create Date',
        ];
    }

    public function saveDialog($apiRequestArray)
    {
        $assistant = new self();
        $assistant->tokens =  var_export($apiRequestArray['request']['nlu']['tokens'], true);
        $assistant->session_id = var_export($apiRequestArray['session']['session_id'], true);
        $assistant->user_id = var_export($apiRequestArray['session']['user_id'], true);
        $assistant->command = var_export($apiRequestArray['request']['command'], true);
        $assistant->json = var_export($apiRequestArray, true);
        $assistant->create_date = date('Y-m-d H:i:s'); //2019-04-30 00:00:00
        $assistant->save();

    }

    /**
     * @var MqttLogic $mqtt
     * @return string
     */
    public function stateAll()
    {
        $mqtt = new MqttLogic();
        $arraySensorState = $mqtt->checkOnline();
        $stateRus = [
            'online'  => 'в сети',
            'offline' => 'не в сети'
        ];
        $online  = 0;
        $offline = 0;
        $request = '';
        foreach ($arraySensorState as $module=>$state) {
            if($state === 'online') {
                $online++;
            }
            if($state === 'offline') {
                $offline++;
            }

            $request .= 'Модуль ' . $module . ' - '.$stateRus[$state].PHP_EOL;
        }

        $stateAll = 'Серый';
        if($online > 0 && $offline === 0) {
            $stateAll = 'Зеленый';
        }
        if($online > 0 && $offline > 0) {
            $stateAll = 'Желтый';
        }
        if($online === 0 && $offline === 0) {
            $stateAll = 'Неизвестен.';
        }
        if($online === 0 && $offline > 0) {
            $stateAll = 'Красный';
        }

        return 'Общий статус: ' . $stateAll . PHP_EOL . $request;
    }

    public function stateSmartWatering()
    {
        $watering = new WateringLogic();
        // in WateringLogic
        return $watering->wateringState();
    }

    public function stateSensors()
    {
        $mqtt = new MqttLogic();
        $request = $mqtt->sensorStatus('alice');
        return $request;
    }

    public function startScheduleWatering()
    {
        Schedule::aliceStartScheduleWatering();
        return 'Запущен цикл автополива.';

    }

    public function stopScheduleWatering()
    {
        // планировщик все таски полива в null
        Schedule::aliceStopScheduleWatering();
        return 'Планировщик событий остановил сценарий полива. Автополив сейчас будет отключен.';

    }

    public function hoseOn()
    {
        $watering = new WateringLogic();
        $watering->MajorOn();
        return 'Центральный клапан включен. Шланг запитан.';

    }

    public function hoseOff()
    {
        $watering = new WateringLogic();
        $watering->MajorOff();
        return 'Центральный клапан выключен. Шланг не запитан.';

    }

    public function alarmOn()
    {
        $watering = new WateringLogic();
        $watering->AlarmOn();
        return 'Все клапаны автополива аварийно отключены. Вы можете проверить это сказав: Общий статус.';

    }

}
