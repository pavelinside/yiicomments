<?php
namespace app\modules\API\controllers;

use app\models\Comment;
use yii\rest\ActiveController;
use yii\web\Response;

use yii\filters\auth\HttpBasicAuth;

class CommentController extends ActiveController {
  public $modelClass = Comment::class;

//  public $serializer = [
//    'class' => 'yii\rest\Serializer',
//    'collectionEnvelope' => 'items',
//  ];


  public function behaviors()  {
    $behaviors = parent::behaviors();
    // enable JSON in browser
    $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
    $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
    //$behaviors['contentNegotiator']['formats']['application/octet-stream'] = Response::FORMAT_JSON;
    $behaviors['contentNegotiator']['formats']['application/xml'] = \yii\web\Response::FORMAT_XML;


    //$behaviors[ 'authenticator' ] = [      'class' => HttpBasicAuth::className() ,    ];

    return $behaviors;
  }

//  protected function verbs() {
//    $verbs = parent::verbs();
//    $verbs =  [
//      'index' => ['GET', 'POST', 'HEAD'],
//      'view' => ['GET', 'HEAD'],
//      'create' => ['POST'],
//      'update' => ['PUT', 'PATCH']
//    ];
//    return $verbs;
//  }

// default
//  protected function verbs()
//  {
//    return [
//      'index' => ['GET', 'HEAD'],
//      'view' => ['GET', 'HEAD'],
//      'create' => ['POST'],
//      'update' => ['PUT', 'PATCH'],
//      'delete' => ['DELETE'],
//    ];
//  }

//  public function actions()  {
//    $actions = parent::actions();
//    unset($actions['delete'], $actions['create'], $actions['view']);
//    return $actions;
//  }

  public function beforeAction($action)  {
    //$this->enableCsrfValidation = false;
    return parent::beforeAction($action);
  }

}
