<?php
/** @var app\models\Product $product */
/** @var $commentForm string */
/** @var $comments string */
/** @var $commentList */

use yii\helpers\Html;

$this->title = $product->title_ru;
?>

<div class="container mt-1">
    <div class="row">
        <div class="col-sm-2">
            <div class="card">
                <div class="card-body">
                  <?php echo Html::img("@web/".$product->image, ['class' => "rounded-circle img-fluid border mx-auto d-block", 'style' => 'max-height:140px']); ?>
                </div>
            </div>
        </div>
        <div class="col-sm-10">
            <div class="card" style="height: 180px;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $product->title; ?></h5>
                    <p>Price: <?php echo $product->price; ?></p>
                    <p class="card-text"><?php echo $product->description; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
echo $commentList;
?>


<?php
echo $commentForm;
?>

