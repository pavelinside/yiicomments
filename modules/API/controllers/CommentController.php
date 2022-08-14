<?php
namespace app\modules\API\controllers;

use app\models\Comment;
use yii\web\Response;

use yii\rest\ActiveController;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;

class CommentController extends ActiveController {
  public $modelClass = Comment::class;

  public function behaviors()  {
    $behaviors = parent::behaviors();

    // enable JSON in browser
    $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
    $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
    //$behaviors['contentNegotiator']['formats']['application/octet-stream'] = Response::FORMAT_JSON;
    $behaviors['contentNegotiator']['formats']['application/xml'] = \yii\web\Response::FORMAT_XML;

    $behaviors['authenticator']['only'] = ['view', 'create', 'update', 'delete'];
    $behaviors['authenticator']['authMethods'] = [
      HttpBasicAuth::class,
      HttpBearerAuth::class,
    ];

    $behaviors['access'] = [
      'class' => AccessControl::class,
      'only' => ['view', 'create', 'update', 'delete'],
      'rules' => [
        [
          'allow' => true,
          'roles' => ['@'],
        ],
      ],
    ];
    return $behaviors;
  }
}
