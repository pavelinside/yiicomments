<?php
/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="container">
  <h1><?php echo Html::encode($this->title); ?></h1>
  <div class="alert alert-danger">
    <?php echo nl2br(Html::encode($message)); ?>
  </div>
</div>