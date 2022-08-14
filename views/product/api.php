<?php
try {
  $this->registerCssFile('@web/js/prettify.css');
  $this->registerJsFile('@web/js/prettify.js');
} catch (Exception $e){
  // log error
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2>API</h2>
                </div>
            </div>
        </div>

        <div class="container mt-3">
            <div class="row">
                <div class="col-12">
                    <ul class="list-group">
                        <li class="list-group-item">/API/comment - Получение списка всех комментариев</li>
                        <li class="list-group-item">/API/author - Получение списка авторов </li>
                        <li class="list-group-item">/API/comment/127.0.0.1 - Получение всех комментариев с одного IP </li>
                        <li class="list-group-item">/API/comment/60 - Получение конкретного комментария по id</li>
                        <li class="list-group-item">/API/comment - Добавление комментариев (POST)</li>
                        <li class="list-group-item">/API/comment/60 Редактирование комментария по id (PUT)</li>
                    </ul>

                    <h2 class="mt-3">Настройка API</h2>
                    <ol>
                        <li>gii создать модуль app\modules\API\Module</li>
                        <li>Настроить его в web.php
                            <pre class="prettyprint">
    'modules' => [
          'API' => [
            'class' => 'app\modules\API\Module',
          ],
    ],

    'rules' => [
            [
              'class' => 'yii\rest\UrlRule',
              'pluralize'=>false, // множественная форма (comments)
              'controller' => ['API/comment'],
            ],
    ],</pre></li>
                        <li>gii Создать контроллер app\modules\API\controllers\CommentController<pre class="prettyprint">
унаследовать от yii\rest\ActiveController
прописать в нем
  public $modelClass = Comment::class;

 а также
  $behaviors['authenticator']['authMethods'] = [
    HttpBasicAuth::class,
    HttpBearerAuth::class,
  ];

  $behaviors['access'] = [
    'class' => AccessControl::class,
    'rules' => [
      [
        'allow' => true,
        'roles' => ['@'],
      ],
    ],
  ];</pre></li>
                        <li>Создать таблицы user(id, username, password_hash и др.) и token(id, userid, token, expired_at)</li>
                        <li>Авторизация. В модуле создать контроллер(SiteController) и модель(LoginForm) для авторизации.
                            <br>Контроллер из Yii::$app->request->bodyParams проверяет логин и пароль и если пользователь существует,
                            <br>то создает токен и возвращает его.</li>
                        <li>В User.php <pre class="prettyprint">
public static function findIdentityByAccessToken($token, $type = null)    {
    return static::find()
      ->joinWith('tokens t')
      ->andWhere(['t.token' => $token])
      ->andWhere(['>', 't.expired_at', time()])
      ->one();
}</pre></li>
                        <li>Примеры запросов <pre class="prettyprint">
Получить токен:
POST https://yiicomments/API/auth
Content-Type: application/x-www-form-urlencoded

username=admin&password=admin


Создать комментарий:
POST https://yiicomments/API/comment
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer GFeM_HLMRJTzx5ojypTziNeieNTv6SHO

productid=1&name=Vasya&rating=4&comment=Нормальный рюкзак пользуюсь&email=vvv@gmail.com


Изменить имя в комментарии:
PUT https://yiicomments/API/comment/60
Accept: application/json
Authorization: Bearer GFeM_HLMRJTzx5ojypTziNeieNTv6SHO

productid=1&name=Александр Волошанюк2&id=60


Получить комментарий по id:
GET https://yiicomments/API/comment/60
Accept: application/json
Authorization: Bearer GFeM_HLMRJTzx5ojypTziNeieNTv6SHO


Получить комментарии (id и name), отсортированные по id, а также зависимую таблицу email
GET https://yiicomments/API/comment?sort=-id&fields=id,name&&expand=email
Accept: application/json
Authorization: Bearer GFeM_HLMRJTzx5ojypTziNeieNTv6SHO </pre></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>