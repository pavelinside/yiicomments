<?php
/** @var string $content */
/** @var yii\web\View $this */

use app\assets\AppAsset;
use yii\bootstrap4\Html;
use yii\helpers\Url;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Yii::$app->language ?>">
<head>
  <meta charset="<?= Yii::$app->charset ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php $this->registerCsrfMetaTags() ?>
  <title><?= Html::encode($this->title) ?></title>
  <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<header>
<!-- Start Top Nav -->
<nav class="navbar navbar-expand-lg bg-dark navbar-light d-lg-block" id="templatemo_nav_top">
    <div class="container text-light">
        <div class="w-100 d-flex justify-content-between">
            <div>
                <i class="fa fa-envelope mx-2"></i>
                <a class="navbar-sm-brand text-light text-decoration-none" href="mailto:info@company.com">supershop@company.com</a>
                <i class="fa fa-phone mx-2"></i>
                <a class="navbar-sm-brand text-light text-decoration-none" href="tel:099-666-6666">099-666-9999</a>
            </div>
            <div>
                <a class="text-light" href="https://fb.com/" target="_blank" rel="sponsored"><i class="fab fa-facebook-f fa-sm fa-fw me-2"></i></a>
                <a class="text-light" href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram fa-sm fa-fw me-2"></i></a>
                <a class="text-light" href="https://twitter.com/" target="_blank"><i class="fab fa-twitter fa-sm fa-fw me-2"></i></a>
                <a class="text-light" href="https://www.linkedin.com/" target="_blank"><i class="fab fa-linkedin fa-sm fa-fw"></i></a>
            </div>
        </div>
    </div>
</nav>
<!-- Close Top Nav -->

<!-- Header -->
<nav style="justify-content: center" class="navbar navbar-light bg-light ">
<ul class="nav">
    <li class="nav-item">
    <a class="navbar-brand text-success logo h1 align-self-center" href="<?php echo Url::to(['product/index']); ?>">
        Блок отзывов
    </a>
    </li>
    <li class="nav-item align-self-center">
        <a class="nav-link" href="<?php echo Url::to(['product/about']); ?>">Про нас</a>
    </li>
    <li class="nav-item align-self-center">
        <a class="nav-link" href="<?php echo Url::to(['product/contact']); ?>">Контакты</a>
    </li>
    <li class="nav-item align-self-center">
        <a class="nav-link" href="<?php echo Url::to(['product/index']); ?>">Магазин</a>
    </li>
    <li class="nav-item align-self-center">
        <a class="nav-link" href="<?php echo Url::to(['product/api']); ?>">API</a>
    </li>
</ul>
</nav>
</header>
<!-- Close Header -->

<?php echo $content; ?>

<!-- Start Footer -->
<footer class="bg-dark" id="tempaltemo_footer">
    <div class="container">
        <div class="row">

            <div class="col-md-4 pt-5">
                <h2 class="h2 text-success border-bottom pb-3 border-light logo">SuperShop</h2>
                <ul class="list-unstyled text-light footer-link-list">
                    <li>
                        <i class="fa fa-phone fa-fw"></i>
                        <a class="text-decoration-none" href="tel: 099-666-9999"> 099-666-9999</a>
                    </li>
                    <li>
                        <i class="fa fa-envelope fa-fw"></i>
                        <a class="text-decoration-none" href="mailto: supershop@company.com">supershop@company.com</a>
                    </li>
                </ul>
            </div>

            <div class="col-md-4 pt-5">
                <h2 class="h2 text-light border-bottom pb-3 border-light">Товары</h2>
                <ul class="list-unstyled text-light footer-link-list">
                    <li><a class="text-decoration-none" href="<?php echo Url::to(['product/view', 'id' => 3]) ?>">Куртка</a></li>
                    <li><a class="text-decoration-none" href="<?php echo Url::to(['product/view', 'id' => 1]) ?>">Рюкзак</a></li>
                    <li><a class="text-decoration-none" href="<?php echo Url::to(['product/view', 'id' => 2]) ?>">Футболка</a></li>
                </ul>
            </div>

            <div class="col-md-4 pt-5">
                <h2 class="h2 text-light border-bottom pb-3 border-light">Информация</h2>
                <ul class="list-unstyled text-light footer-link-list">
                    <li><a class="text-decoration-none" href="<?php echo Url::to(['product/index']); ?>">Блок отзывов</a></li>
                    <li><a class="text-decoration-none" href="<?php echo Url::to(['product/about']); ?>">Про нас</a></li>
                    <li><a class="text-decoration-none" href="<?php echo Url::to(['product/contact']); ?>">Контакты</a></li>
                    <li><a class="text-decoration-none" href="<?php echo Url::to(['product/api']); ?>">API</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="w-100 bg-black py-3">
        <div class="container">
            <div class="row pt-2">
                <div class="col-12">
                    <p class="text-left text-light">
                        Интернет магазин «Supershop» &copy; 2012-2022
                    </p>
                </div>
            </div>
        </div>
    </div>

</footer>
<!-- End Footer -->

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>