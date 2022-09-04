<?php

namespace app\controllers;

class TestController extends \yii\web\Controller {
    public function actionOrderDatePlan() {
      $this->layout = 'test';
      return $this->render('order-date-plan');
    }
}
