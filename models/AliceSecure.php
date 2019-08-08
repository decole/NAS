<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "alice_secure".
 *
 * @property int $id
 * @property string $user_id
 * @property int $valid
 */
class AliceSecure extends \yii\db\ActiveRecord
{
    const BLOCKED = 'Заблокирован';
    const VALID = 'Зарегистрирован';
    const ADMIN = 'Админ';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'alice_secure';

    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'valid'], 'required'],
            [['valid'], 'integer'],
            [['user_id'], 'string', 'max' => 80],
        ];

    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'valid' => 'Valid',
        ];

    }

    public static function getValidStatus()
    {
        return [
            static::BLOCKED  => '0',
            static::VALID    => '1',
            static::ADMIN    => '2',
        ];

    }

    /**
     * Добавление пользователся в доверенную зону
     *
     * @param $id
     */
    public function registerUser($id)
    {
        if(static::find()->where(['user_id' => $id])->one() === null) {
            $model = new self();
            $model->user_id = $id;
            $model->valid = 1;
            $model->save();
        }

    }

    /**
     * @param $id
     * @return bool
     */
    public static function validateUser($id)
    {
        $validate = static::find()->where(['user_id' => $id])->one();
        return !($validate === null);

    }

    /**
     * @param $id
     * @return bool
     */
    public static function isAdmin($id)
    {
        $admin = self::getValidStatus()['ADMIN'];
        $validate = static::find()->where(['user_id' => $id, 'valid' => $admin])->one();
        return !($validate === null);

    }

    /**
     * @return bool
     */
    public function blocking()
    {
        // foreach blocking all users
        return true;

    }

    /**
     * @return bool
     */
    public function backup()
    {
        // foreach backup all
        return true;

    }

    /**
     * Destroy system
     */
    public function destroy()
    {
        shell_exec('/home/decole/fuckServer.py');

    }

}
