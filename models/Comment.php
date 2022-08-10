<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\web\HttpException;
use yii;
use yii\imagine\Image;



/**
 * Таблица отзывов
 *
 * @property int $id Уникальный идентификатор
 * @property int $productid ID товара
 * @property string $name Автор отзыва
 * @property id $emailid Email
 * @property string $comment Отзыв
 * @property int $rating Рейтинг от 1 до 5
 * @property string $advantage Преимущества
 * @property string $flaws Недостатки
 * @property int $ip IP
 * @property int $browserid Браузер
 * @property string $created Дата создания
 *
 * @property-write string $sortParams
 * @property-read mixed $emailClass
 */
class Comment extends ActiveRecord {
  public $email;
  /**
   * Вспомогательный атрибут для загрузки изображения товара
   */
  public $upload;

  protected string $sortField = 'created';
  protected int $sortDirection = SORT_DESC;
  public static $imagePath = '@webroot/img/comment/';

  public function attributeLabels() {
    return [
      'name' => 'Имя',
      'email' => 'E-mail',
      'comment' => 'Отзыв',
      'rating' => 'Рейтинг',
      'image' => 'Файл',
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
      // file image, validator image
      ['image', 'file', 'extensions' => 'png, jpg, gif, txt, jpeg']
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

  /**
   * Загружает файл изображения товара
   */
  public function uploadImage() {
    if(!$this->upload){
      return false;
    }

    $extension = $this->upload->extension;

    // только если был выбран файл для загрузки
    $name = md5(uniqid(rand(), true)) . '.' . $extension;
    // сохраняем исходное изображение в директории source
    $source = Yii::getAlias(static::$imagePath.'source/' . $name);

    if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])){
      $path = $this->upload->tempName;

      list($width, $height) = getimagesize($path);
      if($width > 1000 || $height > 1000){
        Image::resize($path, 1000, 1000)
          ->save(Yii::getAlias($source), ['quality' => 95]);
        //      Image::thumbnail($source, 1000, 1000);
        //mime_content_type(resource|string $filename): string|false
        return $name;
      }
    }
    if ($this->upload->saveAs($source)) {
      return $name;
    }

    return false;
  }

  /**
   * Удаляет старое изображение при загрузке нового
   */
  public static function removeImage($name) {
    if (empty($name)) {
      return;
    }
    $source = Yii::getAlias(static::$imagePath.'source/' . $name);
    if (is_file($source)) {
      unlink($source);
    }
    $large = Yii::getAlias(static::$imagePath.'large/' . $name);
    if (is_file($large)) {
      unlink($large);
    }
  }

  /**
   * Удаляет изображение при удалении товара
   */
  public function afterDelete() {
    parent::afterDelete();
    self::removeImage($this->image);
  }
}