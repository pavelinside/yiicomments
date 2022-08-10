<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        //'css/site.css',
        'css/bootstrap.min.css',
        'css/fontawesome.min.css',
        'css/templatemo.css'
    ];
    public $js = [
        'js/popper.min.js',
        'js/bootstrap.min.js',
        'js/fontawesome_5ac93d4ca8.js',
        'js/bootstrap4-rating-input.min.js',
      'js/bootstrap-filestyle.min.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        //'yii\bootstrap4\BootstrapAsset',
    ];
}