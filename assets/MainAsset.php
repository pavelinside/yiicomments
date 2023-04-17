<?php
namespace app\assets;

use yii\web\AssetBundle;

class MainAsset extends AssetBundle
{
  public $basePath = '@webroot';
  public $baseUrl = '@web';
  public $css = [
    'css/bootstrap.min.css',
    'css/fontawesome.min.css'
  ];
  public $js = [
    'js/popper.min.js',
    'js/bootstrap.min.js',
    'js/fontawesome_5ac93d4ca8.js'
  ];
  public $depends = [
    'yii\web\YiiAsset',
    //'yii\bootstrap4\BootstrapAsset',
  ];
}