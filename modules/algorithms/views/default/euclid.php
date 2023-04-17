<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $result int  */
/* @var $searchForm app\forms\algorithms\EuclidForm */
?>

<?php
$form = ActiveForm::begin([
    'action' => ['euclid'],
    'method' => 'get',
]);
?>
<h3> Алгори́тм Евкли́да для нахождения наибольшего общего делителя двух целых чисел m и n</h3>
<ul>
    <li>1. [Вычитание или нахождение остатка] Вычесть из большего меньшее, получим r (Второй вариант получить остаток от деления)</li>
    <li>2. [Сравнение с нулем] Если r=0, выполнение прекращается; n наибольший общий делитель</li>
    <li>3. [Замещение] Присвоить m <- n, n <- r и вернуться к шагу 1.</li>
</ul>

<div class="row">
    <div class="col-sm-5">
        <?php
            echo $form->field($searchForm, 'number1')->textInput()->label('Число1 2166');
        ?>
    </div>
    <div class="col-sm-5">
        <?php
            echo $form->field($searchForm, 'number2')->textInput()->label('Число2 6099');
        ?>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            <label class="control-label">НОД 57</label>
            <br>
        <?php
        if($result){
            echo Html::tag('span', $result, ['class' => 'badge']);
        }
        ?>
        </div>
    </div>
</div>

<div class="form-group">
    <?php
    echo Html::submitButton(Yii::t('app', 'Calc'), ['class' => 'btn btn-primary']);
    //echo Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']);
    ?>
</div>
<?php ActiveForm::end(); ?>