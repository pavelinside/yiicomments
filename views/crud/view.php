<?php
use yii\helpers\Html;
use yii\helpers\Url;
/*
* @var yii\web\View $this
* @var app\models\Post $model
*/
?>
<p><?= Html::a('< back to emails', Url::toRoute('email/index')); ?></p>
<h2><?= Html::encode($model->name);?></h2>
