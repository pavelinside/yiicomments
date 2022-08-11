<?php
namespace app\controllers;

use app\models\Comment;
use yii\web\Response;
use yii;
//use yii\filters\auth\HttpBasicAuth;
//use yii\filters\AccessControl;

class CommentController extends AppController {

//Авторизация, генерация токена безопасности
//Добавление/Редактирование/ комментариев

//  public function behaviors()  {
//    return [
//      'authenticator' => [
//        'class' => HttpBasicAuth::class,
//      ],
//      'access' => [
//        'class' => AccessControl::class,
//        'only' => ['login', 'logout', 'signup', 'apicomment', 'apiauthor'],
//        'rules' => [
//          [
//            'allow' => true,
//            'actions' => ['login', 'signup', 'apicomment', 'apiauthor'],
//            'roles' => ['?'],
//          ],
//          [
//            'allow' => true,
//            'actions' => ['logout'],
//            'roles' => ['@'],
//          ],
//        ],
//      ],
//    ];
//  }

  /**
   * api/comment      Получение списка всех комментариев
   * api/comment/ip   Получение всех комментариев с одного IP
   * api/comment/id   Получение конкретного комментария по ID
   * @return array
   */
  protected function getApiComment(){
    $comments = Comment::find();

    // comments by ip
    $ip = $this->request->get('ip');
    if($ip){
      $intip = ip2long($ip);
      $comments = $comments->where(['ip' => $intip]);
    }

    // comments by id
    $id = $this->request->get('id');
    if($id){
      $comments = $comments->where(['id' => $id]);
    }
    $comments = $comments->all();
    return $comments;
  }

  /**
   *  API/comment Добавление комментариев
      Comment[productid]: 1
      Comment[name]: q
      Comment[email]: a@gmail.com
      Comment[comment]: 1
      Comment[rating]: 3
      Comment[advantage]:
      Comment[flaws]:
   * @return array
   */
  protected function postApiComment(){
    $model = new Comment();
    $model->isNewRecord = true;
    yii::$app->response->format = Response::FORMAT_JSON;

    if ($model->load(Yii::$app->request->post())) {
      if ($model->save()) {
        $comment = Comment::find()->where(['id' => $model->id])->all();
        return [
          'success' => true,
          'comment' => $comment
        ];
      } else {
        return [
          'success' => false,
          'error' => $model->getErrors()
        ];
      }
    }
    return [
      'success' => false,
      'error' => 'not laded'
    ];
  }

  /**
   *  API/comment/id Редактирование комментариев
  Comment[productid]: 1
  Comment[name]: newName
   * @return array
   */
  protected function putApiComment(){
    $model = new Comment();
    $model->isNewRecord = false;
    yii::$app->response->format = Response::FORMAT_JSON;

    $id = (int) $this->request->get('id');
    $model = Comment::findOne(['id' => $id]);
    if(!$model){
      return ['error' => 'Comment not found'];
    }

    // worked with x-www-form-urlencoded
    $params = Yii::$app->request->bodyParams;
    if ($model->load($params)) {
      if($model->save()){
        $comment = Comment::find()->where(['id' => $model->id])->all();
        return [
          'success' => true,
          'comment' => $comment
        ];
      } else {
        return [
          'success' => false,
          'error' => $model->getErrors()
        ];
      }
    }

    return [
      'success' => false,
      'error' => 'not laded'
    ];
  }

  /**
   * API comment
   * @return array
   */
  public function actionApicomment(){
    yii::$app->response->format = Response::FORMAT_JSON;

    // new comment
    if($this->request->isPost){
      return $this->postApiComment();
    }

    // save comment
    if($this->request->isPut){
      return $this->putApiComment();
    }

    // get comment(s)
    return $this->getApiComment();
  }

  /**
   * api/author
   * Получение списка авторов
   * @return array
   */
  public function actionApiauthor(){
    $authors = Comment::find()->select('name')->distinct()->all();
    yii::$app->response->format = Response::FORMAT_JSON;
    return array_column($authors,'name');
  }

  public function beforeAction($action)  {
    $this->enableCsrfValidation = false;
    return parent::beforeAction($action);
  }
}