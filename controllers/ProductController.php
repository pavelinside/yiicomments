<?php
namespace app\controllers;

use app\models\Comment;
use app\models\Product;
use yii\web\HttpException;
use Yii;
use yii\web\Response;

class ProductController extends AppController {
  public function __construct($id, $module, $config = []){
    parent::__construct($id, $module, $config);
  }

  /**
   * Displays product page.
   *
   * @return array|string|Response
   * @throws HttpException
   */
  public function actionView()  {
    $productId = (int) $this->request->get('id');
    if(!$productId || $productId <= 0){
      throw new HttpException(404,"Товар не найден");
    }
    $product = Product::findOne($productId);
    if(!$product){
      throw new HttpException(404,"Товар $productId не найден");
    }

    $defaultSort = 'dateDesc';
    $post = $this->request->post();

    $perPage = 25;
    $model = new Comment();
    $getComments = function($commentSort) use ($model, $productId, $perPage){
      $model->setSortParams($commentSort);
      $paginationData = $model->getComments($productId, $perPage);
      $comments = $this->renderFile('@app/views/comment/comments.php', ['comments' => $paginationData['comments']]);
      $paginator = $this->renderFile("@app/views/comment/pagination.php", ['pages' => $paginationData['pages']]);
      return [
        'comments' => $comments,
        'paginator' => $paginator
      ];
    };

    // ajax change comments sort
    if(Yii::$app->request->isAjax && isset($post['commentSortChange']) && $post['commentSortChange']){
      $data = $getComments($post['commentSortChange']);
      Yii::$app->response->format = Response::FORMAT_JSON;
      return [
        'success' => true,
        'comments' => $data['comments'],
        'paginator' => $data['paginator'],
        'commentSort' => $post['commentSortChange']
      ];
    }

    // add comment form
    if ($model->load(Yii::$app->request->post())) {
      if ($model->save()) {
        if (Yii::$app->request->isAjax) {
          $commentSort = $post['commentSort'] ?? $defaultSort;
          $data = $getComments($commentSort);
          $message = $this->renderFile("@app/views/app/formSuccess.php", ['message' => 'Ваш отзыв успешно отправлен']);
          Yii::$app->response->format = Response::FORMAT_JSON;
          return [
            'success' => true,
            'message' => $message,
            'comments' => $data['comments'],
            'paginator' => $data['paginator'],
            'commentSort' => $commentSort
          ];
        }
        Yii::$app->session->setFlash('comment-success',true);
        // reload page
        return $this->refresh();
      } else {
        if (Yii::$app->request->isAjax) {
          $allErrors = $model->getErrors();
          $error = $this->renderFile("@app/views/app/formErrors.php", ['allErrors' => $allErrors]);
          Yii::$app->response->format = Response::FORMAT_JSON;
          return [
            'success' => false,
            'error' => $error
          ];
        } else {
          $model->setFlashValidateErrors();
        }
      }
    }
    $commentSort = $this->request->get('sort') ?? $defaultSort;
    $commentParams = [
      'model' => $model,
      'productid' => $product->id,
      'title' => '',
      'h1' => 'Добавление отзыва',
      'commentSort' => $commentSort
    ];
    $commentForm = $this->renderFile('@app/views/comment/add.php', $commentParams);

    // comments html
    $data = $getComments($commentSort);
    $commentList = $this->renderFile('@app/views/comment/commentlist.php', [
      'comments' => $data['comments'],
      'paginator' => $data['paginator'],
      'productid' => $product->id,
      'commentSort' => $commentSort
    ]);

    return $this->render('view', [
      'product' => $product,
      'commentForm' => $commentForm,
      'commentList' => $commentList
    ]);
  }

  /**
   * Displays products page.
   *
   * @return string
   */
  public function actionIndex()  {
    $products = Product::find()->all();
    return $this->render('index', [
      'products' => $products
    ]);
  }

  public function actionAbout(){
    return $this->render('about');
  }

  public function actionApi(){
    return $this->render('api');
  }

  public function actionContact(){
    return $this->render('contact');
  }
}