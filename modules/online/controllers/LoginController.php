<?php

namespace app\modules\online\controllers;

use yii\web\Controller;
use app\modules\online\services\LoginService;

/**
 * Default controller for the `algorithms` module
 */
class LoginController extends Controller
{
    private LoginService $service;

    public function __construct($id, $module, LoginService $service, $config = []) {
        $this->service = $service;
        parent::__construct($id, $module, $config);
    }

    /**
     * magic Находилась в главном контроллере, парсит метод контроллера, чтобы получить представление по умолчанию
     * get default view for controller method Autorization\Controller\Auth\loginAction -> Autorization:Auth:login
     * @return string
     * @throws \Exception
     */
    protected function getDefaultView(){
        $method = debug_backtrace()[1]['function'];
        if(strpos($method, 'Action') !== false){
            $method = substr($method, 0, strlen($method) - 6);
        }
        $class = get_called_class();
        $view = str_replace('\\', ':', $class);
        $view = str_replace(':Controller:', ':', $view).":".$method;
        return $view;
    }

    public function actionLogin(){
        if(array_key_exists('id', $_SESSION)){
            header("Location: ".'some base url');
        }

        //$view = $this->getDefaultView();
        $params = [
            'login' => '',
            'error' => ''
        ];

        if (isset($_POST["login"]) && isset($_POST["password"])){
            if (!$this->service->authorize($_POST["login"], $_POST["password"])){
                $params['error'] = 'Логин или пароль неверны';
                $params['login'] = $_POST["login"];
            } else {
                header("Location: ".'some base url');
            }
        } else if($_POST){
            $params['error'] = 'Не заданы логин или пароль';
        }

        return $this->render('login', $params);
    }

    public function actionLogout(){
        $this->service->sessionDestroy();
        header("Location: ".'some base url');
    }
}
