<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
/*
* @var yii\db\ActiveRecord $model
 * @var array $fileAttributes
*/

$formOptions = [];
if (!empty($fileAttributes)) {
    $formOptions['options'] = ['enctype' => 'multipart/form-data'];
}

?>

<h1>
    <?= Yii::t('app', 'Create') . " " . ucfirst(basename(str_replace('\\', '/', get_class($model)))) ?>
    <?= Html::a(Yii::t('app', 'Index'), ['index'], ['class' => 'btn btn-primary']) ?>
</h1>

<?php $form = ActiveForm::begin($formOptions); ?>

<?php
$form->errorSummary($model);
$firstGeneralError = $model->getFirstError('');
if ($firstGeneralError) {
    echo "<p style='color:red'>" . $firstGeneralError . "</p>";
}

    // Для каждого атрибута генерируем поле формы
    foreach ($model->attributes() as $attribute) {
        if($attribute === 'id'){
            continue;
        }

        // Проверяем, является ли атрибут полем для загрузки файлов
        if (isset($fileAttributes[$attribute])) {
            echo $form->field($model, $attribute)->fileInput();
            continue;
        }

        $inputType = 'textInput'; // Дефолтный тип
        // Определяем тип поля на основе типа данных атрибута
        if (is_bool($model->$attribute)) {
            $inputType = 'checkbox'; // Для boolean полей
        } elseif (is_numeric($model->$attribute)) {
            $inputType = 'numberInput'; // Для числовых полей
        } elseif ($model->hasMethod('get' . ucfirst($attribute) . 'List')) {
            // Для полей с методом get<Attribute>List (например, getStatusList)
            $inputType = 'dropDownList';
        }

        // Генерация поля формы в зависимости от типа
        switch ($inputType) {
            case 'checkbox':
                echo $form->field($model, $attribute)->checkbox();
                break;

            case 'numberInput':
                echo $form->field($model, $attribute)->input('number');
                //echo $form->field($model, $attribute)->numberInput();
                //echo $form->field($model, $attribute)->textInput(['type' => 'number']);
                break;

            case 'dropDownList':
                $listMethod = 'get' . ucfirst($attribute) . 'List';
                echo $form->field($model, $attribute)->dropDownList($model->$listMethod());
                break;

            default:
                echo $form->field($model, $attribute)->textInput();
                break;
        }
    }
?>

<?= Html::submitButton(Yii::t('app', 'Create'), ['class' => 'btn btn-primary']) ?>
<?php ActiveForm::end(); ?>
