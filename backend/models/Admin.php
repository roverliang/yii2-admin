<?php

namespace backend\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\helpers\ArrayHelper;

/**
 * Admin model
 *
 * @property string $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $mobile
 * @property string $avatar
 * @property integer $sex
 * @property string $last_login_ip
 * @property string $last_login_time
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 */
class Admin extends ActiveRecord implements IdentityInterface
{
    const STATUS_FORBID = 0;//账户禁止
    const STATUS_ACTIVE = 1;//账户正常
    const SEX_SECRET = 0;//性别保密
    const SEX_MAN = 1;//性别男
    const SEX_WOMAN = 2;//性别女

    public $passwordRepeat;//确认密码
    public $role;//所属角色

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * 场景，区分
     * 注意修改时可能会影响console中的初始化后台用户功能
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_FORBID]],
            [['username'], 'match', 'pattern' => '/^[a-zA-Z]\w*$/i'],
            [['username'], 'unique'],
            //由于下三个都是唯一的所以必须填写，但是由于console中的初始化后台用户时越简单越好，所以设定场景
            [['username', 'email', 'mobile'], 'required', 'on' => ['create', 'update', 'modify']],
            //创建时必须填写确认密码
            [['password_hash', 'passwordRepeat'], 'required', 'on' => 'create'],
            //在创建和修改自身时需要填写确认密码
            ['passwordRepeat', 'compare', 'compareAttribute' => 'password_hash', 'on' => ['create', 'modify']],
            ['email', 'email'],
            ['email', 'unique'],
            ['mobile', 'match', 'pattern' => '/^1(3|4|5|7|8)[0-9]\d{8}$/'],
            ['mobile', 'unique'],
            [['created_at', 'updated_at', 'last_login_time'], 'integer'],
            ['sex', 'in', 'range' => [self::SEX_SECRET, self::SEX_MAN, self::SEX_WOMAN]],
            [['auth_key', 'last_login_ip', 'password_hash'], 'safe'],
            [['avatar'], 'file', 'extensions' => 'png, jpg'],
        ];
    }

    public function fields()
    {
        return parent::fields(); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('admin', 'ID'),
            'username' => Yii::t('admin', 'Username'),
            'auth_key' => Yii::t('admin', 'Auth Key'),
            'password_hash' => Yii::t('admin', 'Password Hash'),
            'passwordRepeat' => Yii::t('admin', 'Password Repeat'),//增加确认密码
            'password_reset_token' => Yii::t('admin', 'Password Reset Token'),
            'email' => Yii::t('admin', 'Email'),
            'mobile' => Yii::t('admin', 'Mobile'),
            'avatar' => Yii::t('admin', 'Avatar'),
            'sex' => Yii::t('admin', 'Sex'),
            'last_login_ip' => Yii::t('admin', 'Last Login Ip'),
            'last_login_time' => Yii::t('admin', 'Last Login Time'),
            'status' => Yii::t('admin', 'Status'),
            'created_at' => Yii::t('admin', 'Created At'),
            'updated_at' => Yii::t('admin', 'Updated At'),
            'role' => Yii::t('admin', 'Role'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int)substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
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
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     *  获取下拉菜单列表或者某一名称
     * @param bool $key
     * @return array|mixed
     */
    public static function getStatusOptions($key = false)
    {
        $arr = [
            self::STATUS_FORBID => Yii::t('admin', 'Forbid'),
            self::STATUS_ACTIVE => Yii::t('admin', 'Active')
        ];
        return $key === false ? $arr : ArrayHelper::getValue($arr, $key, Yii::t('common', 'Unknown'));
    }

    /**
     *  获取性别下拉菜单
     * @param bool $key
     * @return array|mixed
     */
    public static function getSexOptions($key = false)
    {
        $arr = [
            self::SEX_SECRET => Yii::t('admin', 'Secret'),
            self::SEX_MAN => Yii::t('admin', 'Man'),
            self::SEX_WOMAN => Yii::t('admin', 'Woman')
        ];
        return $key === false ? $arr : ArrayHelper::getValue($arr, $key, Yii::t('common', 'Unknown'));
    }

    /**
     * 根据ID获取名称
     * @param $id
     * @return array|string
     */
    public static function getUsernameOptions($id = false)
    {
        $arr = static::find()->select('username')->indexBy('id')->asArray()->column();
        if ($id === false) {
            return $arr;
        } else {
            return isset($arr[$id]) ? $arr[$id] : Yii::t('common', 'Unknown');
        }
    }

    /**
     * 存储前的动作
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        //如果是新增，则自动产生
        if ($this->isNewRecord) {
            $this->generateAuthKey();
            $this->generatePasswordResetToken();
            $this->setPassword($this->password_hash);
        }
        return parent::beforeSave($insert);
    }
}