<?php
/* @var $this yii\web\View */
/* @var $productid  */
/* @var $comments  */
/* @var $paginator  */
/* @var $commentSort */

use yii\helpers\Url;

$js = <<<JS
/**
* обновить ссылки в пагинаторе
* TODO переделать - сделать наследника LinkPager или свой widget paginator https://overcoder.net/q/2841199/linkpager-для-ajax-сгенерированных-результатов
* @param commentSort
*/
function refreshPaginationPages(commentSort){
  $("a[class~='page-item'],a[class~='page-link']").each(function(){
      if(this.href && this.href.indexOf('/sort/') === -1){
        //this.href += "/sort/"+value;
        $(this)[0].setAttribute('href', this.href + "/sort/"+commentSort);
      }
  });
}
function initSort(url){
  $("#commentSort").change(function(){
    const value = $(this).val();
    $("#sortField").val(value);

    // TODO spinner
    
    // send data to server
    $.ajax({
      url: url,
      type: 'POST',
      data: {
        commentSortChange: value
      }
    })
    .done(function(data) {
      console.log(data);
      if(data && data.comments){
        $('#commentContainer').html(data.comments);
      }
      if(data && data.paginator){
        $('#paginatorContainer').html(data.paginator);
        refreshPaginationPages(value);
      }
    })
    .fail(function () {
      console.log('fail');
    })
  });  
}

JS;
$url = Url::to(['product/view', 'id' => $productid]);
$js .= " initSort('".$url."');";
$js .= "$(function(){ refreshPaginationPages('".$commentSort."'); });";
$this->registerJs($js, $this::POS_READY);

if($comments){
  ?>
    <div class="container mt-5">
        <h3>Отзывы</h3>

        <table class="table table-striped">
            <tbody>
            <tr><td>
                    <select style="float: right" id="commentSort" title="Сортировка">
                        <option value="dateDesc" <?php echo $commentSort === "dateDesc" ? 'SELECTED' : '';?> >По убыванию даты</option>
                        <option value="dateAsc" <?php echo $commentSort === "dateAsc" ? 'SELECTED' : '';?>>По возрастанию даты</option>
                        <option value="ratingDesc" <?php echo $commentSort === "ratingDesc" ? 'SELECTED' : '';?>>По убыванию рейтинга</option>
                        <option value="ratingAsc" <?php echo $commentSort === "ratingAsc" ? 'SELECTED' : '';?>>По возрастанию рейтинга</option>
                    </select>
                </td></tr>
            </tbody>
        </table>

        <table class="table table-striped">
        <tbody id="commentContainer">
  <?php
}

echo $comments;

if($comments){
  ?>
    </tbody></table>
    <nav id="paginatorContainer" class="my-4">
      <?php
      echo $paginator;
      ?>
    </nav>

    </div>
  <?php
}