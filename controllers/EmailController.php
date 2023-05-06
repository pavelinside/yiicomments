<?php

namespace app\controllers;

use Yii;
use yii\helpers\Html;
use yii\web\Controller;

class EmailController extends Controller
{
    public function actions()
    {
        return [
            // declares "error" action using a class name
            'error' => 'yii\web\ErrorAction',

             'view' => [
                'class' => 'app\actions\ViewAction',
                'modelClass' => \app\models\Email::class,
            ],
            'create' => [
                'class' => 'app\actions\CreateAction',
                'modelClass' => \app\models\Email::class,
            ],
            'delete' => [
                'class' => 'app\actions\DeleteAction',
                'modelClass' => \app\models\Email::class,
            ],
            'index' => [
                'class' => 'app\actions\IndexAction',
                'modelClass' => \app\models\Email::class,
                'pageSize' => 6
            ],
        ];
    }
}
