<?php
namespace app\modules\crud\actions;

use yii\base\Action;
use yii\web\NotFoundHttpException;

class ViewAction extends Action
{
    public $modelClass;
    public array $fileAttributes = [];

    public function run($id)
    {
        $class = $this->modelClass;
        if (($model = $class::findOne($id)) === null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return $this->controller->render('@app/modules/crud/views/crud/view', [
            'model' => $model,
            'fileAttributes' => $this->fileAttributes,
        ]);
    }
}
