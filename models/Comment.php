<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\web\HttpException;
use yii;

/**
 *
 * @property-write string $sortParams
 * @property-read mixed $emailClass
 */
class Comment extends ActiveRecord {
  public $email;

  protected string $sortField = 'created';
  protected int $sortDirection = SORT_DESC;

  public function attributeLabels() {
    return [
      'name' => 'Имя',
      'email' => 'E-mail',
      'comment' => 'Отзыв',
      'rating' => 'Рейтинг',
      'advantage' => 'Преимущества',
      'flaws' => 'Недостатки'
    ];
  }

  public function rules() {
    return [
      // удалить пробелы для полей name и email
      [['name', 'email'], 'trim'],
      ['productid', 'required', 'message' => 'Товар не выбран'],
      ['productid', 'integer'],
      ['name', 'required', 'message' => 'Поле «Имя» обязательно для заполнения'],
      ['email', 'required', 'message' => 'Поле «Email» обязательно для заполнения'],
      ['email', 'email', 'message' => 'Поле «Ваш email» должно быть адресом почты'],
      ['comment', 'required', 'message' => 'Поле «Отзыв» обязательно для заполнения'],
      ['rating', 'required', 'message' => 'Поле «Рейтинг» обязательно для заполнения'],
      ['rating', 'in', 'range' => [1, 2, 3, 4, 5], 'message' => 'Не указан рейтинг'],
      ['name', 'string', 'max' => 100, 'tooLong' => 'Поле «Имя» должно быть длиной не более 100 символов'],
      ['email', 'string', 'max' => 250, 'tooLong' => 'Поле «Email» должно быть длиной не более 250 символов'],
      [['comment', 'advantage', 'flaws'], 'string', 'max' => 1000, 'tooLong' => 'Поле должно быть длиной не более 1000 символов'],
    ];
  }

  public static function tableName()  {
    return "comment";
  }

//  public function getBrowser() {
//    return $this->hasOne(Browser::class, ['id' => 'browserid']);
//  }

  public function getEmailClass(){
    return $this->hasOne(Email::class, ['id' => 'emailid']);
  }

  public function beforeSave($insert) {
    if (!parent::beforeSave($insert)) {
      return false;
    }

    if (!$this->validate()) {
      $this->setFlashValidateErrors();
      return false;
    }

    // check product
    $modelProduct = new Product();
    if(!$modelProduct::findOne($this->productid)){
      throw new HttpException(500,"Товар не найден");
    }

    // save email
    $modelEmail = new Email();
    try{
      $emailID = $modelEmail->addByName($this->email);
      $this->emailid = $emailID;
    } catch(yii\db\Exception $e){
      throw new HttpException(500,"Ошибка добавления отзыва");
    }

    // userAgent
    $this->browserid = NULL;
    $userAgent = yii::$app->getRequest()->getUserAgent();
    if($userAgent){
      $modelBrowser = new Browser();
      try{
        $browserID = $modelBrowser->addByName($userAgent);
        $this->browserid = $browserID;
      } catch(yii\db\Exception $e){
        throw new HttpException(500,"Ошибка добавления отзыва");
      }
    }

    // get IP
    $ip = yii::$app->getRequest()->getUserIP();
    $this->ip = NULL;
    if($ip){
      // long2ip(int $ip): string|false
      $this->ip = ip2long($ip);
    }

    return true;
  }

  /**
   * @return void
   */
  public function setFlashValidateErrors(){
    yii::$app->session->setFlash('comment-success',false);

    // сохраняем в сессии введенные пользователем данные
    yii::$app->session->setFlash('comment-data', [
      'name' => $this->name,
      'email' => $this->email,
      'comment' => $this->comment,
      'rating' => $this->rating,
      'advantage' => $this->advantage,
      'flaws' => $this->flaws
    ]);
    // errors  [
    //  'name' => ['Поле «Имя» обязательно для заполнения',],
    //  'email' => ['Поле «Email» обязательно для заполнения', 'Поле «Email» должно быть адресом почты']
    // ]
    yii::$app->session->setFlash('comment-errors', $this->getErrors());
  }

  /**
   * @param int $productId
   * @param int $limit
   * @return array
   */
  public function getComments(int $productId, int $limit = 25) :array{
    $query = Comment::find()
      ->where(['productid' => $productId])
      ->with('emailClass'); // жадная загрузка

    //$countQuery = clone $query;
    $config = [
      'totalCount' => $query->count(),
      'pageSize' => $limit,
      //'forcePageParam' => false, // убрать параметры с первой страницы !!! не ставить это
      'pageSizeParam' => false,  // убрать per page
    ];
    $pages = new yii\data\Pagination($config);

    $comments = $query->offset($pages->offset)
      ->orderBy([$this->sortField => $this->sortDirection])
      ->limit($pages->limit)
      ->all();
    return ['comments' => $comments, 'pages' => $pages];
  }

  public function setSortParams(string $commentSort){
    switch ($commentSort){
    case "dateAsc":
      $this->sortField = "created";
      $this->sortDirection = SORT_ASC;
    break;
    case "ratingDesc":
      $this->sortField = "rating";
      $this->sortDirection = SORT_DESC;
    break;
    case "ratingAsc":
      $this->sortField = "rating";
      $this->sortDirection = SORT_ASC;
    break;
    default:
      $this->sortField = "created";
      $this->sortDirection = SORT_DESC;
      break;
    }
  }
}