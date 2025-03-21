<?php
namespace app\modules\crud\actions;

use yii\base\Action;
use yii\web\NotFoundHttpException;

class DeleteAction extends Action
{
    public $modelClass;

    /**
     * @throws NotFoundHttpException
     */
    public function run($id): \yii\web\Response
    {
        $class = $this->modelClass;
        if (($model = $class::findOne($id)) === null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $model->delete();
        return $this->controller->redirect(['index']);
    }
}
