<?php

namespace app\controllers;

use yii\web\Controller;
use yii\helpers\Html;

class BlogController extends Controller
{
    public function actionRssFeed($param)
    {
        return $this->renderContent('This is RSS feed for our blog and ' . $param);
    }

    public function actionArticle($alias)
    {
        return $this->renderContent('This is an article with alias ' . $alias);
    }

    public function actionList()
    {
        return $this->renderContent('Blog\'s articles here');
    }

    public function actionHiTech()
    {
        return $this->renderContent('Just a test of action which contains more than one words in the name');
    }

    public function actionView($alias)
    {
        return $this->renderContent(
            Html::tag('h2','Showing post with alias ' . Html::encode($alias))
        );
    }

    public function actionIndex($type = 'posts', $order = 'DESC')
    {
        return $this->renderContent(
            Html::tag('h2','Showing ' . Html::encode($type) . ' ordered ' . Html::encode($order))
        );
    }

    public function actionHello($name)
    {
        return $this->renderContent(
            Html::tag('h2','Hello, ' . Html::encode($name) . '!')
        );
    }
}
