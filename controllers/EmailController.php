<?php

namespace app\controllers;

use app\models\Email;
use app\modules\crud\actions\CreateAction;
use app\modules\crud\actions\DeleteAction;
use app\modules\crud\actions\IndexAction;
use app\modules\crud\actions\ViewAction;
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
                'class' => ViewAction::class,
                'modelClass' => Email::class,
            ],
            'create' => [
                'class' => CreateAction::class,
                'modelClass' => Email::class,
            ],
            'delete' => [
                'class' => DeleteAction::class,
                'modelClass' => Email::class,
            ],
            'index' => [
                'class' => IndexAction::class,
                'modelClass' => Email::class,
                'pageSize' => 6
            ],
        ];
    }
}
