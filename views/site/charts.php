<?php

/* @var $this yii\web\View */

/** @var array $charts */


use yii\helpers\Html;
use yii\helpers\Json;

$this->title = 'Графики параметров действующих сенсоров';
//$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-contact">
    <div class="row">
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Пристройка <b class="dateIs"><?=date('Y-m-d');?></b></h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas class="sensorchart" data-topic="margulis/temperature" id="lineChart2" height="250"></canvas>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col (RIGHT) -->
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Холодная прихожка <b class="dateIs"><?=date('Y-m-d');?></b></h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas class="sensorchart" data-topic="holl/temperature" id="lineChart1" height="250"></canvas>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col (RIGHT) -->
    </div>
    <div class="row">
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Низа <b class="dateIs"><?=date('Y-m-d');?></b></h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas class="sensorchart" data-topic="underflor/temperature" id="lineChart3" height="250"></canvas>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col (RIGHT) -->
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Коридор в низа <b class="dateIs"><?=date('Y-m-d');?></b></h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas class="sensorchart" data-topic="underground/temperature" id="lineChart4" height="250"></canvas>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col (RIGHT) -->
        <div class="col-md-12 pager">
            <div class="btn-group btn-group-sm" role="group" aria-label="Small button group">
                <input type="text" id="isDate" value="current" hidden>
                <button type="button" class="btn btn-default" id="prev-date" ><</button>
                <button type="button" class="btn btn-default" id="next-date" >></button>
            </div>
        </div>
    </div>
</div>