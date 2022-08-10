<?php
/* @var $this yii\web\View */
/* @var $model  */
/* @var $h1  */
/* @var $productid  */
/* @var $commentSort  */

use yii\widgets\ActiveForm;
use yii\helpers\Html;

if(isset($title) && $title){
  $this->title = $title;
}

try {
  $this->registerJsFile('@web/js/sendAjaxForm.js'); // js только в этом шаблоне
} catch (Exception $e){
    // log error
}
$js = <<<JS
$(function(){
  var fileSelector = '#comment-image';
    $(fileSelector).parent().prepend(
      '<span id="detachFile" title="Открепить файл" style="color:red;display:none;float: right;cursor: pointer;">&nbsp;X&nbsp;</span>'
    );
    $('#detachFile').click(function(){
      $(fileSelector).val('').change();
      $('#detachFile').css({display:'none'});
    });
    $(fileSelector).filestyle();
    $(fileSelector).change(function(){
        $('#detachFile').css({display: 'inline'});
        return true;
    });
});

$( "#comment-form" ).on('beforeSubmit', function() {
  const form = $(this);
  
  // show spinner
  const btn = $('#btnCommentSend');
  const oldHtml = btn.html();
  $(btn)
    .prop("disabled", true)
    .html(
      `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`
    );
  
  // send ajax
  sendAjaxForm(document.getElementById('comment-form'))
    .then((data) => {
        $(btn).prop("disabled", false).html(oldHtml);
        if (data.success) {
          // данные прошли валидацию, сообщение было отправлено
          $('#response').html(data.message ? data.message : '');
          if(data.comments){
            $("#commentMainContainer").show();
            $('#commentContainer').html(data.comments);
          }
          if(data && data.paginator){
            $('#paginatorContainer').html(data.paginator);
            if (typeof window.refreshPaginationPages === "function") {
              const commentSort = $('#sortField').val();
              console.log('addcommentSort', commentSort);
              window.refreshPaginationPages(commentSort);
            }
          }
          
          // reset
          form.children('.has-success').removeClass('has-success');
          form[0].reset();
          // reset rating
          form.find('i[class~="fa-remove"]').click();
        }
	})
	.catch((e) => {
      $(btn).prop("disabled", false).html(oldHtml);
      console.log(e);
	  alert(e.error);
    });
  
  // cancel send form
  return false; 
});
JS;
$this->registerJs($js, $this::POS_READY);

/*
 * Если данные формы не прошли валидацию, получаем из сессии сохраненные
 * данные, чтобы заполнить ими поля формы, не заставляя пользователя
 * заполнять форму повторно
 */
$name = '';
$email = '';
$comment = '';
$rating = '';
$advantage = '';
$flaws = '';
if (Yii::$app->session->hasFlash('comment-data')) {
  $data = Yii::$app->session->getFlash('comment-data');
  $name = Html::encode($data['name']);
  $email = Html::encode($data['email']);
  $comment = Html::encode($data['comment']);
  $rating = Html::encode($data['rating']);
  $advantage = Html::encode($data['advantage']);
  $flaws = Html::encode($data['flaws']);
}

$success = false;
if (Yii::$app->session->hasFlash('comment-success')) {
  $success = Yii::$app->session->getFlash('comment-success');
}
?>

<style>
    .fa-remove {
        visibility: hidden;
        width: 1px;
        height: 0;
    }
    .help-block {
        color: red;
    }
</style>

<div class="container mt-5">
    <div id="response">
      <?php if (!$success){ ?>
        <?php if (Yii::$app->session->hasFlash('comment-errors')) {
          $allErrors = Yii::$app->session->getFlash('comment-errors');
          echo $this->renderFile("@app/views/app/formErrors.php", ['allErrors' => $allErrors]);
        ?>
        <?php } ?>
      <?php } else {
          echo $this->renderFile("@app/views/app/formSuccess.php", ['message' => 'Ваш отзыв успешно отправлен']);
      } ?>
    </div>

    <h3><?= Html::encode($h1) ?></h3>

  <?php
  $config = ['id' => 'comment-form', 'options' => ['novalidate' => '']];
  $form = ActiveForm::begin($config);
  ?>
  <input type="hidden" name="productid" value="<?php echo $productid; ?>">
  <input type="hidden" id="sortField" name="commentSort" value="<?php echo $commentSort; ?>">
  <?= Html::activeHiddenInput($model, 'productid', ['value' => $productid]) ;?>
  <?= $form->field($model, 'name')->textInput(['value' => $name]); ?>
  <?= $form->field($model, 'email')->input('email', ['value' => $email]); ?>
  <?= $form->field($model, 'comment')->textarea(['rows' => 5, 'value' => $comment]); ?>
  <?= $form->field($model, 'rating')->input('number', ['class'=>'rating', 'data-clearable'=>'remove', 'value' => $rating]); ?>
  <?=
    $form->field($model, 'image')->input('file', [
    'class' => "filestyle", 'data-placeholder' => "Прикрепить изображение или текстовый файл", 'data-text'=>"Выбрать",
      "accept"=>".jpg, .jpeg, .png, .gif, .txt"
  ])->label('<span>Файл</span>&nbsp;<span id="detachFile" title="Открепить файл" style="color:red;display:none;">X</span>');
  ?>
  <?= $form->field($model, 'advantage')->textarea(['rows' => 5, 'value' => $advantage]); ?>
  <?= $form->field($model, 'flaws')->textarea(['rows' => 5, 'value' => $flaws]); ?>
  <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary', 'id' => 'btnCommentSend']); ?>
  <?php ActiveForm::end(); ?>
</div>