<?php

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
            'product/<id:\d+>/comments/<page:\d+>/sort/<sort:\w+>' => 'product/view',
            'product/<id:\d+>/comments/<page:\d+>' => 'product/view',
            'product/<id:\d+>' => 'product/view',
//              'GET API/comment/<ip:([0-9]{1,3}[\.]){3}[0-9]{1,3}>' => 'comment/apicomment',
//              'GET,PUT API/comment/<id:\d+>' => 'comment/apicomment',
//              'GET,POST API/comment' => 'comment/apicomment',
//              'GET API/author' => 'comment/apiauthor',

            'API/auth' => 'API/site/login',
            [
              'class' => 'yii\rest\UrlRule',
              'pluralize'=>false, //отключить преобразование во множественную форму
              'controller' => ['API/comment'],
            ],
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
