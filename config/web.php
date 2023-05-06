<?php

use app\cart\storage\SessionStorage;
Yii::$container->setSingleton(app\cart\ShoppingCart::class);
Yii::$container->set(app\cart\storage\StorageInterface::class,
    function() {
        return new SessionStorage(Yii::$app->session,'primary-cart');
    }
);


$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'layout' => 'base',
    'name' => 'Comments',
    'language' => 'ru',
    'defaultRoute' => 'product/index',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'API' => [
            'class' => 'app\modules\API\Module',
        ],
        'algorithms' => [
            'class' => 'app\modules\algorithms\Module',
        ]
    ],
    'components' => [
        'formatter' => [
          //'dateFormat' => 'dd.MM.yyyy'
          'dateFormat' => 'php:d F Y'
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'hzgsz7iT6sLsXFSTkjCWlutlxO4M-QMF',
            'baseUrl' => '',
//            'parsers' => [
//              'application/json' => 'yii\web\JsonParser',
//            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            //'enableSession' => false
        ],
        'errorHandler' => [
            'errorAction' => 'app/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
          'enablePrettyUrl' => true,
          'showScriptName' => false,
          'enableStrictParsing' => true,

          'rules' => [
            '' => 'product/index',
            'product/about' => 'product/about',
            'product/api' => 'product/api',
            'product/contact' => 'product/contact',
            'product/<id:\d+>/comments/<page:\d+>/sort/<sort:\w+>' => 'product/view',
            'product/<id:\d+>/comments/<page:\d+>' => 'product/view',
            'product/<id:\d+>' => 'product/view',
            'API/auth' => 'API/site/login',
            'GET API/comment/<ip:([0-9]{1,3}[\.]){3}[0-9]{1,3}>' => 'API/comment/commentip',
            'GET API/author' => 'API/comment/commentauthor',
            [
              'class' => 'yii\rest\UrlRule',
              'pluralize'=>false, //отключить преобразование во множественную форму
              'controller' => ['API/comment'],
            ],
            'algorithms' => 'algorithms/default/index',
            'algorithms/euclid' => 'algorithms/default/euclid',
            'algorithms/progression-geometric' => 'algorithms/default/progression-geometric',
            'algorithms/progression-arithmetic' => 'algorithms/default/progression-arithmetic',

            'cart/index' => 'cart/index',
            'cart/add' => 'cart/add',
            'cart/delete' => 'cart/delete',

              '<alias:about>' => 'test/page', // about
              'page/<alias>' => 'test/page',  // page/about page/test

              'test/urls' => 'test/urls',
              'blog/<alias:[-a-z]+>' => 'blog/view',    // blog/test
              '<type:(archive|posts)>' => 'blog/index', // archive posts
              '<type:(archive|posts)>/<order:(DESC|ASC)>' => 'blog/index', // posts/ASC
              'sayhello/<name>' => 'blog/hello',        // sayhello

              'email/<action>' => 'email/<action>'

          ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
