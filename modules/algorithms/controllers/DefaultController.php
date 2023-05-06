<?php

namespace app\modules\algorithms\controllers;

use Yii;
use yii\web\Controller;
use app\forms\algorithms\EuclidForm;
use app\forms\algorithms\ProgressionGeometricForm;
use app\forms\algorithms\ProgressionArithmeticForm;
use app\services\AlgorithmsService;

/**
 * Default controller for the `algorithms` module
 */
class DefaultController extends Controller
{
    private AlgorithmsService $service;

    public function __construct($id, $module, AlgorithmsService $service, $config = []) {
        $this->service = $service;
        parent::__construct($id, $module, $config);
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex() :string {
        return $this->render('index');
    }

    /**
     * алгоритм евклида наибольший общий делитель
     * @return string
     */
    public function actionEuclid() :string {
        $result = 0;
        $form = new EuclidForm();
        if($form->load(Yii::$app->request->get()) && $form->validate()){
            $result = $this->service->get_greatest_common_divisor($form->number1, $form->number2);
        }
        return $this->render('euclid', [
            'searchForm' => $form,
            'result' => $result
        ]);
    }

    /**
     * Сумма геометрической прогрессии
     * @return string
     */
    public function actionProgressionGeometric() :string  {
        $result = 0;
        $form = new ProgressionGeometricForm();

        if($form->load(Yii::$app->request->get()) && $form->validate()){
            $result = $this->service->get_progression_geometric_sum($form->a, $form->n, $form->q);
        }

        return $this->render('progression-geometric', [
            'searchForm' => $form,
            'result' => $result
        ]);
    }

    /**
     * Сумма арифметической прогрессии
     * @return string
     */
    public function actionProgressionArithmetic() :string  {
        $result = 0;
        $form = new ProgressionArithmeticForm();

        if($form->load(Yii::$app->request->get()) && $form->validate()){
            $result = $this->service->get_progression_arithmetic_sum($form->a, $form->n, $form->d);
        }

        return $this->render('progression-arithmetic', [
            'searchForm' => $form,
            'result' => $result
        ]);
    }
}
