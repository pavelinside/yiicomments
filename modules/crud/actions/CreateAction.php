<?php
namespace app\modules\crud\actions;

use Yii;
use yii\base\Action;
use yii\web\UploadedFile;

class CreateAction extends Action
{
    public $modelClass;
    public $fileAttributes = [];

    public function run()
    {
        $model = new $this->modelClass();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            // Обработка файлов
            foreach ($this->fileAttributes as $fileAttribute => $uploadPath) {
                $uploadedFile = UploadedFile::getInstance($model, $fileAttribute);
                if ($uploadedFile) {
                    $fileName = uniqid() . '.' . $uploadedFile->extension;
                    $filePath = Yii::getAlias($uploadPath) . '/' . $fileName;

                    if ($uploadedFile->saveAs($filePath)) {
                        $model->$fileAttribute = $fileName;
                    } else {
                        $model->addError($fileAttribute, Yii::t('app', 'File upload error'));
                    }
                }
            }

            if ($model->validate() && $model->save()) {
                return $this->controller->redirect(['view', 'id' => $model->getPrimaryKey()]);
            }
        }

        return $this->controller->render('@app/modules/crud/views/crud/create', [
            'model' => $model,
            'fileAttributes' => $this->fileAttributes
        ]);
    }
}