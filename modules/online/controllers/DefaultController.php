<?php

namespace app\modules\online\controllers;

use yii\web\Controller;
use app\modules\online\services\AutoloadService;

class DefaultController extends Controller
{
    private AutoloadService $service;

    public function __construct($id, $module, AutoloadService $service, $config = []) {
        $this->service = $service;
        parent::__construct($id, $module, $config);
    }

    /**
     * @return string
     */
    public function actionIndex() :string {
        return $this->render('index');
    }

    /**
     * Пример функции для автоподгрузки классов взято из Вестника и переделано
     * @return string
     */
    public function actionAutoload() :string  {
        return $this->render('autoload');
    }
}
