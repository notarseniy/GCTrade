<?php

namespace app\modules\users\models\forms;

use app\modules\users\models\User;
use yii\base\InvalidParamException;
use yii\base\Model;
use Yii;

/**
 * Password reset form
 */
class ResetPasswordForm extends Model
{
	public $password;
	private $_user;

    public function attributeLabels()
    {
        return [
            'password' => Yii::t('users', 'RESET_PASSWORD_FORM_PASSWORD'),
        ];
    }

	/**
	 * Creates a form model given a token.
	 *
	 * @param string $token
	 * @param array $config name-value pairs that will be used to initialize the object properties
	 * @throws \yii\base\InvalidParamException if token is empty or not valid
	 */
	public function __construct($token, $config = [])
	{
		if (empty($token) || !is_string($token)) {
			throw new InvalidParamException(Yii::t('users', 'RESET_PASSWORD_FORM_EMPTY_TOKEN'));
		}
		$this->_user = User::findByPasswordResetToken($token);
		if (!$this->_user) {
			throw new InvalidParamException(Yii::t('users', 'RESET_PASSWORD_FORM_INVALID_TOKEN'));
		}
		parent::__construct($config);
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['password', 'required'],
			['password', 'string', 'min' => 6],
		];
	}

	/**
	 * Resets password.
	 *
	 * @return boolean if password was reset.
	 */
	public function resetPassword()
	{
		$user = $this->_user;
		$user->password = $this->password;
		$user->removePasswordResetToken();
		return $user->save();
	}
}
