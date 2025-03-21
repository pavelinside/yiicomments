<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $products */

$this->title = Yii::$app->name;
?>
<section class="container py-5">
  <div class="row">
    <?php
    foreach ($products as $product){
    ?>
    <div class="col-12 col-md-4 p-5 mt-3">
        <h5 class="text-center mt-3 mb-3"><?php echo $product->title_ru ?></h5>
        <a href="<?php echo Url::to(['product/view', 'id' => $product->id]) ?>" title="Добавить отзыв">
          <?php echo Html::img("@web/img/products/".$product->image, ['class' => "rounded-circle img-fluid border"]) ?>
        </a>
    </div>
    <?php
    }
    ?>
  </div>
</section>
<?php
?>