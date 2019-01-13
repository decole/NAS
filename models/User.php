<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $auth_key
 * @property string $access_token
 * @property string $email_confirm
 * @property string $email
 * @property bool   $is_confirm
 * @property string $create_date
 * @property string $update_date
 * @property string $notification
 * @mixin TimestampBehavior
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    const SCENARIO_PROFILE = 'profile';


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username'], 'required'],
            [['username', 'password', 'auth_key', 'access_token', 'email_confirm', 'email'], 'string', 'max' => 255],
            [['username'], 'unique'],
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
                'updatedAtAttribute' => 'update_date',
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Логин'),
            'password' => Yii::t('app', 'Пароль'),
            'auth_key' => Yii::t('app', 'Auth Key'),
            'access_token' => Yii::t('app', 'Access Token'),
            'email_confirm' => Yii::t('app', 'Email Confirm'),
            'email' => Yii::t('app', 'Email'),
            'create_date' => Yii::t('app', 'Create Date'),
            'update_date' => Yii::t('app', 'Update Date'),
        ];
    }

    public function scenarios()
    {

        return array_merge(parent::scenarios(),[
            self::SCENARIO_PROFILE => ['notification'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::find()->where(['id' => $id])->one();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::find()->where(['accessToken' => $token])->one();
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        /** @var User $user */
        $user = static::find()->where(['username' => $username])->one();

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    public function setPassword($password)
    {
        $this->password = Yii::$app->getSecurity()->generatePasswordHash($password);
    }

    public function clearPassword()
    {
//        $this->setPwd(Yii::$app->getSecurity()->generateRandomString());
        $this->password = null;
    }

    public function getPassword()
    {
        return null;
    }

    public function verifyToken($token)
    {
        return static::find()->where(['email_confirm' => $token])->one();
    }

//    /**
//     * @param $event
//     * @return bool
//     *
//     */

//    public function afterLogin($event)
//    {
//        $this->update_date = date('Y-m-d H:i:s');
//        $this->save(false);
//        return true;
//    }
}
