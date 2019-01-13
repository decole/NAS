<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "weather".
 *
 * @property integer $id
 * @property string $temperature
 * @property string $spec
 * @property string $date
 */
class Weather extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'weather';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['temperature', 'date'], 'required'],
//            [['date'], 'safe'],
//            [['temperature'], 'string', 'max' => 6],
//            [['spec'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'temperature' => Yii::t('app', 'Temperature'),
            'spec' => Yii::t('app', 'Spec'),
            'date' => Yii::t('app', 'Date'),
        ];
    }
}
