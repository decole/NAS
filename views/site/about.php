<?php

/* @var $this yii\web\View */

use yii\helpers\Html;


$this->title = 'О нас';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        Проект пишется на Yii2 Framework. Реализация бота для Telegram и сервиса грабинга погоды AccuWeather.
    </p>
<!--    --><?php //yii\helpers\someHelper::conrad();?>
<!--    <code>--><?//= __FILE__ ?><!--</code>-->
</div>

<div class="inner">
    <?php
    if (!extension_loaded('mosquitto')) {
        echo "Mosquitto not loaded";
    } else {
        echo "Mosquitto loaded";
    }
    ?>
</div>