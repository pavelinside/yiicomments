<?php
namespace app\modules\crud\actions;

use yii\base\Action;
use yii\data\Pagination;

class IndexAction extends Action
{
    public $modelClass;
    public $pageSize = 3;

    public function run()
    {
        //print_r($this);die;
        $class = $this->modelClass;
        $query = $class::find();
        $countQuery = clone $query;
        $pages = new Pagination([
            'totalCount' => $countQuery->count(),
        ]);
        $pages->setPageSize($this->pageSize);
        $models = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();
        return $this->controller->render('@app/modules/crud/views/crud/index', [
            'pages' => $pages,
            'models' => $models
        ]);
    }
}
