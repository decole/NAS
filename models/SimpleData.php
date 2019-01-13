<?php

namespace app\models;

use Yii;
//use yii\base\Model;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%simple_data}}".
 *
 * This model is working for Form on site / Name / Phone / email
 *
 * @property string $id
 * @property string $name
 * @property string $phone
 * @property string $email
 */
class SimpleData extends ActiveRecord
{
//    public $name;
//    public $email;
//    public $phone;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%simple_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'phone', 'email'], 'string', 'max' => 255],
            // name, email, subject and body are required
            //[['name', 'email', 'phone'], 'required'],
            // email has to be a valid email address
            ['email', 'email'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('app', 'Name'),
            'phone' => Yii::t('app', 'Phone'),
            'email' => Yii::t('app', 'Email'),
        ];
    }


}
