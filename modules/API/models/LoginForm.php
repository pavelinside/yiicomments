<?php
namespace app\modules\API\models;

use app\models\Token;
use app\models\User;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model {
  public $username;
  public $password;

  private $_user;

  /**
   * @inheritdoc
   */
  public function rules()  {
    return [
      // username and password are both required
      [['username', 'password'], 'required'],
      // password is validated by validatePassword()
      ['password', 'validatePassword'],
    ];
  }

  /**
   * Validates the password.
   * This method serves as the inline validation for password.
   *
   * @param string $attribute the attribute currently being validated
   * @param array $params the additional name-value pairs given in the rule
   */
  public function validatePassword($attribute, $params)  {
    if (!$this->hasErrors()) {
      $user = $this->getUser();
      if (!$user || !$user->validatePassword($this->password)) {
        $this->addError($attribute, 'Incorrect username or password.');
      }
    }
  }

  /**
   * @return Token|null
   */
  public function auth()  {
    if ($this->validate()) {
      $token = new Token();
      $token->userid = $this->getUser()->id;
      // 1 day
      $token->generateToken(time() + 3600 * 24);
      return $token->save() ? $token : null;
    } else {
      return null;
    }
  }

  /**
   * Finds user by [[username]]
   *
   * @return User|null
   */
  protected function getUser()  {
    if ($this->_user === null || $this->_user->id === null) {
      $this->_user = User::findByUsername($this->username);
    }

    return $this->_user;
  }
}