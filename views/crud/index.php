<?php

use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;

/*
* @var yii\web\View $this
* @var yii\data\Pagination $pages
* @var array $models
*/
?>

<h1>Posts</h1>
<?= Html::a('+ Create a email', Url::toRoute('email/create')); ?>
<?php foreach ($models as $model):?>
    <h3><?= Html::encode($model->name);?></h3>
    <p>
        <?= Html::a('view', Url::toRoute(['email/view', 'id' => $model->id]));?> |
        <?= Html::a('delete', Url::toRoute(['email/delete', 'id' => $model->id]));?>
    </p>
<?php endforeach; ?>
<?= LinkPager::widget(['pagination' => $pages,]); ?>