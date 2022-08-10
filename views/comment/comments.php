<?php
use yii\helpers\Html;

/* @var $comments array */
/* @var $pages */

$month = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
foreach ($comments as $comment){
  $time = strtotime($comment->created);
  //$created = \Yii::$app->formatter->asDate($comment->created, 'php:d F Y'); // en month - bad
  $created = date('j ', $time) . $month[date('n', $time)-1] . date(' Y', $time). date(" H:i", $time);
  echo "<tr><td>
    <b style='color:green'>".Html::encode($comment->name)."</b>
     <span class='ml-5'>".$comment->emailClass->name."</span>
      <span style='float: right'>".$created."</span>
  </td></tr>";

  echo "<tr><td>
        <input type='number' class='rating' value='".$comment->rating."' data-clearable='remove'>
    </td></tr>";

  echo "<tr><td>".Html::encode($comment->comment)."</td></tr>";

  if($comment->image){
    $img = Yii::getAlias('@webroot') . '/img/comment/source/' .  $comment->image;
    if (is_file($img)) {
      $url = Yii::getAlias('@web') . '/img/comment/source/' .  $comment->image;
      echo "<tr><td>Файл: ".Html::a(Html::encode($comment->image), $url, ['target' => '_blank'])."</td></tr>";
    }
  }

  if($comment->advantage){
    echo "<tr><td><b>Преимущества</b></td></tr>";
    echo "<tr><td>".Html::encode($comment->advantage)."</td></tr>";
  }

  if($comment->flaws){
    echo "<tr><td><b>Недостатки</b></td></tr>";
    echo "<tr><td>".Html::encode($comment->flaws)."</td></tr>";
  }
}