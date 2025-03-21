<?php
use yii\helpers\Url;
use yii\helpers\Html;

echo "<pre>\n";
echo Html::a('Product list', Url::toRoute('/product/index'), ['target' => '_blank'])."\n";
echo Html::a('Email list', Url::toRoute('/email/index'), ['target' => '_blank'])."\n";
echo Html::a('Корзина', Url::toRoute('cart/index'), ['target' => '_blank'])."\n";
echo Html::a('Алгоритм Евклида', Url::toRoute('/algorithms/default/euclid'), ['target' => '_blank'])."\n";
echo Html::a('Геометрическая прогрессия', Url::toRoute('/algorithms/default/progression-geometric'), ['target' => '_blank'])."\n";
echo Html::a('Арифметическая прогрессия', Url::toRoute('algorithms/default/progression-arithmetic'), ['target' => '_blank'])."\n";
echo "</pre>";