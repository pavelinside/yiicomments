<?php
use yii\helpers\Html;

/*
* @var yii\web\View $this
* @var yii\db\ActiveRecord $model
* @var array $fileAttributes
*/
?>

<p>
    <?php
        echo Html::a(Yii::t('app', 'Index'), ['index'], ['class' => 'btn btn-primary']) . "&nbsp;";
        echo Html::a(Yii::t('app', 'Edit'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']);
    ?>
</p>

<h2>

    <?php
        $modelName = basename(str_replace('\\', '/', get_class($model))) ;
        echo Yii::t('app', 'View') . ' ' . ucfirst($modelName) . ' ID: ' . $model->id;
    ?>
</h2>

<?php

foreach ($model->attributes() as $attribute) {
    if ($attribute === 'id') {
        continue;
    }

    echo "<p><strong>" . Yii::t('app', ucfirst($attribute)) . ":</strong> ";

    $value = $model->$attribute;
    if (isset($fileAttributes[$attribute]) && !empty($value)) {
        echo Html::img($fileAttributes[$attribute] . '/' . $value, ['width' => '200']) . "</p>";
        continue;
    }
    echo Html::encode($value) . "</p>";
}
?>