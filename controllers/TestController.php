<?php

namespace app\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\Controller;
use app\components\CustomFilter;
use yii\filters\AccessControl;

class TestController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => CustomFilter::className(),
            ],
            'access2' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'actions' => ['user']
                    ],
                    [
                        'allow' => true,
                        'roles' => ['?'],
                        'actions' => ['bage', 'index', 'contact', 'page']
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->session->setFlash('error','only for registered users.');
                    $this->redirect(['page/index']);
                },
            ],
        ];
    }

    //
    public function actions()
    {
        return [
            'page' => [
                'class' => 'yii\web\ViewAction', // /contact
            ]
        ];
    }

    public function actionIndex()
    {
        return $this->renderContent(Html::tag('h2',
            'Index action'
        ));
    }

    // /about /page/<alias>
    public function actionBage($alias)
    {
        return $this->renderContent(Html::tag('h2',
            'Page is '. Html::encode($alias)
        ));
    }

    // /test/urls
    public function actionUrls()
    {
        return $this->render('urls');
    }
}
