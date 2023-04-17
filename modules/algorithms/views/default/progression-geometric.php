<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $result int  */
/* @var $searchForm app\forms\algorithms\ProgressionGeometricForm */
?>

<?php
$form = ActiveForm::begin([
    'action' => ['progression-geometric'],
    'method' => 'get',
]);
?>
    <h3> Сумма геометрической прогрессии a + ax + ... + ax^n = a1*( q^n - 1 ) / (q - 1)</h3>
    <ul>
        <li>q <> 1 - Знаменатель</li>
        <li>a - Первый элемент</li>
        <li>n - Количество элементов</li>
    </ul>

    <div class="row">
        <div class="col-sm-3">
            <?php
            echo $form->field($searchForm, "a")->textInput()->label('Первый элемент a');
            ?>
        </div>
        <div class="col-sm-3">
            <?php
            echo $form->field($searchForm, 'n')->textInput()->label('Количество элементов n');
            ?>
        </div>
        <div class="col-sm-3">
            <?php
            echo $form->field($searchForm, 'q')->textInput()->label('Знаменатель q');
            ?>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label class="control-label">Сумма</label>
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
<?php ActiveForm::end(); ?><?php
