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
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-sitemap"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Пристройка</span>
                    <span class="info-box-number">t.
                        <span class="sensor-state" data-topic="margulis/temperature">
                            <?=$margulis_temperature?>
                        </span> &#8451;
                    </span>
                    <span class="info-box-number"><i class="fa fa-fire"></i>
                        <span class="sensor-state" data-topic="margulis/humidity">
                            <?=$margulis_humidity?>
                        </span> %
                    </span>
                </div><!-- /.info-box-content -->
            </div><!-- /.info-box -->
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-sitemap"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Холодная прихожка</span>
                    <span class="info-box-number">t.
                        <span class="sensor-state" data-topic="holl/temperature">
                            <?=$holl_temperature ?>
                        </span> &#8451;
                    </span>
                    <span class="info-box-number"><i class="fa fa-fire"></i>
                        <span class="sensor-state" data-topic="holl/humidity">
                            <?=$holl_humidity ?>
                        </span> %
                    </span>
                </div><!-- /.info-box-content -->
            </div><!-- /.info-box -->
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-sitemap"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Низа</span>
                    <span class="info-box-number">t.
                        <span class="sensor-state"  data-topic="underflor/temperature">
                            <?=$underflor_temperature ?>
                        </span> &#8451;
                    </span>
                    <span class="info-box-number"><i class="fa fa-fire"></i>
                        <span class="sensor-state" data-topic="underflor/humidity">
                            <?=$underflor_humidity ?>
                        </span> %
                    </span>
                </div><!-- /.info-box-content -->
            </div><!-- /.info-box -->
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-sitemap"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Коридор в низа</span>
                    <span class="info-box-number">t.
                        <span class="sensor-state" data-topic="underground/temperature">
                            <?=$underground_temperature ?>
                        </span> &#8451;
                    </span>
                    <span class="info-box-number"><i class="fa fa-fire"></i>
                        <span class="sensor-state" data-topic="underground/humidity">
                            <?=$underground_humidity ?>
                        </span> %
                    </span>
                </div><!-- /.info-box-content -->
            </div><!-- /.info-box -->
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Gismeteo Bar</h3>
            </div><!-- /.box-header -->
            <div class="box-body">
                <!-- Gismeteo informer START -->
                <link rel="stylesheet" type="text/css" href="https://nst1.gismeteo.ru/assets/flat-ui/legacy/css/informer.min.css">
                <div id="gsInformerID-ThDE2N1BwRI0IR" class="gsInformer" style="margin: auto;">
                    <div class="gsIContent">
                        <div id="cityLink">
                            <a href="https://www.gismeteo.ru/weather-kamyshin-5064/" target="_blank">Погода в Камышине</a>
                        </div>
                        <div class="gsLinks">
                            <table>
                                <tr>
                                    <td>
                                        <div class="leftCol">
                                            <a href="https://www.gismeteo.ru/" target="_blank">
                                                <img alt="Gismeteo" title="Gismeteo" src="https://nst1.gismeteo.ru/assets/flat-ui/img/logo-mini2.png" align="middle" border="0" />
                                                <span>Gismeteo</span>
                                            </a>
                                        </div>
                                        <div class="rightCol">
                                            <a href="https://www.gismeteo.ru/weather-kamyshin-5064/2-weeks/" target="_blank">Прогноз на 2 недели</a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <script async src="https://www.gismeteo.ru/api/informer/getinformer/?hash=ThDE2N1BwRI0IR" type="text/javascript"></script>
                <!-- Gismeteo informer END -->
            </div><!-- /.box-body -->
        </div>
    </div>
    <div class="col-lg-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Acuweather Bar</h3>
            </div><!-- /.box-header -->
            <div class="box-body">
                <div class="accuweather">
                    <p>AccuWeather Now:</p>
                    <ul>
                        <li class="accu">temperature: <?= /** @var \app\models\Weather $acuweather */
                            $acuweather->temperature; ?></li>
                        <li class="accu">status: <?=$acuweather->spec; ?></li>
                    </ul>
                </div>
            </div><!-- /.box-body -->
        </div>
    </div>
</div>