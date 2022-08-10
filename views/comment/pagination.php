<?php
use yii\widgets\LinkPager;

/* @var $pages  */

try {
  echo LinkPager::widget([
    'pagination' => $pages,
    'options' => [
      'class' => 'pagination pagination-circle pg-blue mb-0'
    ],
    'linkOptions' => ['class' => 'page-link'],
    'disabledListItemSubTagOptions' => ['class' => 'page-link disabled'],
    'disabledPageCssClass' =>  ['class' => 'page-item disabled'],
    'activePageCssClass' => ['class' => 'page-item active ']
  ]);
} catch (Exception $e) {
  // log error
}