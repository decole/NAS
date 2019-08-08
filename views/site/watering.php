<?php

/* @var $this yii\web\View */

/** @var \app\models\Arduino $arduino1 */
/** @var \app\models\Arduino $arduino2 */
/** @var \app\models\Arduino $arduino3 */
/** @var \app\models\Arduino $arduino4 */

use yii\helpers\Html;

$this->title = 'Данные сенсоров';
//$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-contact">
    <div class="row bottom-10">
        <div class="col-lg-6">
            <p>Карта объекта:</p>
        </div>
        <div class="col-lg-6">

        </div>
    </div>
    <div class="row">
        <?php
        foreach ($ralays as $key=>$value){
        ?>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3><i class="fa fa-sliders"></i> <?=$value['name']?></h3>
                        <p class="relay-status" data-id="<?=$key?>"><?=$value['relay1'] ?></p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a class="small-box-footer <?=$value['relay1'] ?> relay-control" data-id="<?=$key?>">Переключить <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div><!-- ./col -->
        <?php
        }
        ?>
    </div>
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-yellow stateEmergencyStop">
                <div class="inner">
                    <h3><i class="fa fa-sliders"></i> Откл. все</h3>
                    <p>Аварийный останов</p>
                </div>
                <div class="icon">
                    <i class="ion ion-bag"></i>
                </div>
                <a class="small-box-footer off emergencyStop" data-id="water/alarm">Аварийно остановить <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div><!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-yellow leakage-control" data-id="water/leakage">
                <div class="inner">
                    <h3><i class="fa fa-sliders"></i> Датчик</h3>
                    <p class="leakage-status" data-id="water/leakage">Норма</p>
                    <audio id="carteSoudCtrl">
                        <source id="emergency" src="https://uberserver.ru/sounds/emergency.mp3" type="audio/mpeg">
                    </audio>
                </div>
                <div class="icon">
                    <i class="ion ion-bag"></i>
                </div>
                <a class="small-box-footer">Датчик протечки воды </a>
            </div>
        </div><!-- ./col -->
    </div>

</div>
