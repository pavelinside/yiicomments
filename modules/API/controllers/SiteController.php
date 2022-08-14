<?php
namespace app\modules\API\controllers;

use Yii;
use yii\rest\Controller;
use app\modules\API\models\LoginForm;

class SiteController extends Controller {
  public function actionIndex()  {
    return 'api';
  }

  public function actionLogin()  {
    $model = new LoginForm();
    $model->load(Yii::$app->request->bodyParams, '');
    if ($token = $model->auth()) {
      return $token;
    }
    // rest Controller process all model errors
    return $model;
  }

  protected function verbs()  {
    // login only for post query
    return [
      'login' => ['post'],
    ];
  }
}