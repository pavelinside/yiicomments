<?php
namespace app\controllers;

use yii\web\Controller;

class AppController extends Controller {
  public function actions() {
    return [
      'error' => [
        'class' => 'yii\web\ErrorAction',
      ],
    ];
  }
}