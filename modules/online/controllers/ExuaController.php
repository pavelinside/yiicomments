<?php

namespace app\modules\online\controllers;

use yii\web\Controller;
use app\modules\online\services\ExuaService;

class ExuaController extends Controller
{
    private ExuaService $service;

    public function __construct($id, $module, ExuaService $service, $config = []) {
        $this->service = $service;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(){
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $arr = $this->service->_pluginMain($url);
        $html = "";
        foreach($arr as $link){
            $html .= $link;
        }
        $html .= "<form method='GET' target='_blank'>
			<input name='word' value=''>
			<input type='hidden' name='control' value='exua'>
			<input type='hidden' name='mtd' value='search'>
			<button>Search</button>
		</form>";

        header('Content-Type: text/html; charset=utf-8');

        exit($html);
        //return $this->render('login', $params);
    }

    public function actionSearch(){
        $word = isset($_GET['word']) ? $_GET['word'] : '';

        $arr = $this->service->_pluginSearch($word);
        $html = "";
        foreach($arr as $link){
            $html .= $link;
        }
        header('Content-Type: text/html; charset=utf-8');
        exit($html);
    }
}
