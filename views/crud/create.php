<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
/*
* @var yii\web\View $this
*/
?>

<h1><?= Yii::t('app', 'Create email'); ?></h1>
<?php $form = ActiveForm::begin();?>
<?php $form->errorSummary($model); ?>
<?= $form->field($model, 'name')->textInput() ?>
<?= Html::submitButton(Yii::t('app', 'Create'), ['class' => 'btn btn-primary']) ?>
<?php ActiveForm::end(); ?>
