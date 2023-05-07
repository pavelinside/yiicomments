<?php

namespace app\controllers;

use Yii;
use yii\helpers\Html;
use yii\web\Controller;
use app\components\CustomFilter;

class TestController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => CustomFilter::className(),
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->renderContent(Html::tag('h2',
            'Index action'
        ));
    }
    public function actionPage($alias)
    {
        return $this->renderContent(Html::tag('h2',
            'Page is '. Html::encode($alias)
        ));
    }

    public function actionUrls()
    {
        return $this->render('urls');
    }
}
