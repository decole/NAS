<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "mqtt".
 *
 * @property int $id
 * @property string $topic
 * @property string $payload
 * @property string $datetime
 */
class Mqtt extends \yii\db\ActiveRecord
{
    const SENSOR_HOLL_TEMPERATURE        = 'holl/temperature';
    const SENSOR_HOLL_HUMIDITY           = 'holl/humidity';
    const SENSOR_UNDERFLOR_TEMPERATURE   = 'underflor/temperature';
    const SENSOR_UNDERFLOR_HUMIDITY      = 'underflor/humidity';
    const SENSOR_UNDERGROUND_TEMPERATURE = 'underground/temperature';
    const SENSOR_UNDERGROUND_HUMIDITY    = 'underground/humidity';
    const SENSOR_MARGULIS_TEMPERATURE    = 'margulis/temperature';
    const SENSOR_MARGULIS_HUMIDITY       = 'margulis/humidity';
    const SENSOR_WATER_LEAKAGE           = 'water/leakage';

    const SWIFT_WATER_MAJOR              = 'water/major';
    const SWIFT_WATER_1                  = 'water/1';
    const SWIFT_WATER_2                  = 'water/2';
    const SWIFT_WATER_3                  = 'water/3';
    const SWIFT_DEFAULT                  = 'noname';

    const MARGULIS                       = 'MARGULIS';
    const WATERING                       = 'WATERING';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mqtt';

    }

    /**
     * Registered sensor topics
     *
     * @return array
     */
    public static function getSensorNames(): array
    {
        return [
            static::SENSOR_HOLL_TEMPERATURE        => 'температура в холодной прихожке',
            static::SENSOR_HOLL_HUMIDITY           => 'влажность в холодной прихожке',
            static::SENSOR_UNDERFLOR_TEMPERATURE   => 'температура в низах',
            static::SENSOR_UNDERFLOR_HUMIDITY      => 'влажность в низах',
            static::SENSOR_UNDERGROUND_TEMPERATURE => 'температура под низами',
            static::SENSOR_UNDERGROUND_HUMIDITY    => 'влажность под низами',
            static::SENSOR_MARGULIS_TEMPERATURE    => 'температура в пристройке',
            static::SENSOR_MARGULIS_HUMIDITY       => 'влажность в пристройке',
            static::SENSOR_WATER_LEAKAGE           => 'умный полив-датчик протечки воды',
        ];

    }

    /**
     * Registered swift topics
     *
     * @return array
     */
    public static function getSwiftNames(): array
    {
        return [
            static::SWIFT_WATER_MAJOR => 'главный клапан полива',
            static::SWIFT_WATER_1     => 'клапан 1 полива',
            static::SWIFT_WATER_2     => 'клапан 2 полива',
            static::SWIFT_WATER_3     => 'клапан 3 полива',
            static::SWIFT_DEFAULT     => 'не идентифицированное устройство',
        ];

    }

    /**
     * List topics from check module is online
     *
     * @return array
     */
    public static function getModuleNames(): array
    {
        return [
            static::MARGULIS => ['name' => 'модуль пристройка-прихожка-низа', 'check_topic' => 'margulis/temperature'],
            static::WATERING => ['name' => 'автополив', 'check_topic' => 'water/check/major'],
        ];

    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['topic', 'payload', 'datetime'], 'required'],
            [['datetime'], 'safe'],
            [['topic'], 'string', 'max' => 35],
            [['payload'], 'string', 'max' => 10],
            [['topic'], 'in', 'range' => array_keys(static::getSensorNames())],
        ];

    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'topic' => 'Topic',
            'payload' => 'Payload',
            'datetime' => 'Datetime',
        ];

    }
}
