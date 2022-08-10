<?php
/* @var $allErrors array  */
?>

<div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Закрыть">
    <span aria-hidden="true">&times;</span>
  </button>
  <p>При заполнении формы допущены ошибки</p>
  <ul>
    <?php foreach ($allErrors as $errors): ?>
      <?php foreach ($errors as $error): ?>
        <li><?= $error; ?></li>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </ul>
</div>