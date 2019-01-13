<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "arduino".
 *
 * @property integer $id
 * @property integer $arduino
 * @property integer $temperaturу
 * @property integer $pressure
 * @property integer $humidity
 * @property string $date
 */
class Arduino extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'arduino';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['arduino', 'temperaturу', 'pressure', 'humidity'], 'integer'],
            [['date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'arduino' => Yii::t('app', 'Arduino'),
            'temperaturу' => Yii::t('app', 'Temperaturу'),
            'pressure' => Yii::t('app', 'Pressure'),
            'humidity' => Yii::t('app', 'Humidity'),
            'date' => Yii::t('app', 'Date'),
        ];
    }
}
