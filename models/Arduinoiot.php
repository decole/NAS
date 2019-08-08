<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "arduinoiot".
 *
 * @property integer $id
 * @property string $name
 * @property string $topic
 * @property integer $relay1
 */
class Arduinoiot extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'arduinoiot';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id', 'relay1'], 'integer'],
            [['name', 'topic'], 'string', 'max' => 250],
            [['id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'relay1' => Yii::t('app', 'Relay1'),
            'name' => Yii::t('app', 'Name'),
            'topic' => Yii::t('app', 'Topic'),
        ];
    }


}
