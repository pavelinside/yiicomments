<?php
namespace app\modules\API;

use yii\web\JsonResponseFormatter;
use yii\web\Response;

/**
 * https://dev58.ru/articles/yii2_nastroyka_rest_api
 * API module definition class
 */
class Module extends \yii\base\Module {
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\API\controllers';

    /**
     * {@inheritdoc}
     */
    public function init() {
      parent::init();

      // parse json and xml request
      $this->components['request']['parsers'] = [
        'application/json' => 'yii\web\JsonParser',
        //'application/xml' => 'yii\web\XmlParser',
        'text/xml' => 'yii\web\XmlParser',
      ];

      // view json in yii debug bar
      $this->components['response']['formatters'] = [
        Response::FORMAT_JSON => [
          'class' => JsonResponseFormatter::class,
          'prettyPrint' => YII_DEBUG, // используем "pretty" в режиме отладки
          'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ]
      ];

      $this->components['user'] = [
        //'identityClass' => 'app\models\User',
        'enableAutoLogin' => false,
        'enableSession' => false
      ];
    }
}
