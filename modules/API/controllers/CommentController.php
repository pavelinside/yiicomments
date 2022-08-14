<?php
namespace app\modules\API\controllers;

use app\models\Comment;
use yii\web\HttpException;
use yii\web\Response;

use yii;
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
    $behaviors['contentNegotiator']['formats']['application/xml'] = Response::FORMAT_XML;

    //$behaviors['authenticator']['only'] = ['create', 'update', 'delete'];
    //$behaviors['authenticator']['only'] = ['ipcomment', 'view', 'create', 'update', 'delete'];
    $behaviors['authenticator']['authMethods'] = [
      HttpBasicAuth::class,
      HttpBearerAuth::class,
    ];

    $behaviors['access'] = [
      'class' => AccessControl::class,
      //'only' => ['ipcomment', 'index', 'view', 'create', 'update', 'delete'],
      //'only' => ['create', 'update', 'delete'],
      'rules' => [
        [
          'allow' => true,
          'roles' => ['@'],
        ],
      ],
    ];
    return $behaviors;
  }

  /**
   * API comment
   * api/comment/ip   Получение всех комментариев с одного IP
   * @return array
   */
  public function actionCommentip(){
    $ip = $this->request->get('ip');
    if(!$ip){
      throw new HttpException(404,"ip не задан");
    }

    $intip = ip2long($ip);
    $comments = Comment::find()->where(['ip' => $intip])->all();
    return $comments;
  }

  /**
   * api/author
   * Получение списка авторов
   * @return array
   */
  public function actionCommentauthor(){
    $authors = Comment::find()->select('name')->distinct()->all();
    //yii::$app->response->format = Response::FORMAT_JSON;
    return array_column($authors,'name');
  }

  public function verbs()  {
    $verbs = parent::verbs();
    $verbs['commentip'] = ['get'];
    $verbs['commentauthor'] = ['get'];
    return $verbs;
  }
}
