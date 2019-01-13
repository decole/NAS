<?php

/* @var $this yii\web\View */

/** @var array $charts */


use yii\helpers\Html;

$this->title = 'Графики параметров действующих сенсоров';
//$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-contact">
    <div class="row">
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Пристройка</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="lineChart2" height="250"></canvas>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col (RIGHT) -->
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Холодная прихожка</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="lineChart1" height="250"></canvas>
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
                    <h3 class="box-title">Низа</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="lineChart3" height="250"></canvas>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col (RIGHT) -->
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Коридор в низа</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="lineChart4" height="250"></canvas>
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col (RIGHT) -->
    </div>
</div>
<script>
    var areaChartData1 = {
        labels: [<?php echo implode(',', $labels); ?>],
        datasets: [
            {
                label: "Electronics",
                fillColor: "rgba(60,141,188,0.9)",
                strokeColor: "rgba(60,141,188,0.8)",
                pointColor: "#3b8bba",
                pointStrokeColor: "rgba(60,141,188,1)",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(60,141,188,1)",
                data: [65, 59, 80, 81, 56, 55, 40]
            },
            {
                label: "Digital Goods",
                fillColor: "rgba(210, 214, 222, 1)",
                strokeColor: "rgba(210, 214, 222, 1)",
                pointColor: "rgba(210, 214, 222, 1)",
                pointStrokeColor: "#c1c7d1",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: [28, 48, 40, 19, 86, 27, 90]
            }
        ]
    };
    var areaChartData2 = {
        labels: [<?php echo implode(',', $labels); ?>],
        datasets: [
            {
                label: "Electronics",
                fillColor: "rgba(60,141,188,0.9)",
                strokeColor: "rgba(60,141,188,0.8)",
                pointColor: "#3b8bba",
                pointStrokeColor: "rgba(60,141,188,1)",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(60,141,188,1)",
                data: [55, 39, 60, 21, 16, 35, 60]
            },
            {
                label: "Digital Goods",
                fillColor: "rgba(210, 214, 222, 1)",
                strokeColor: "rgba(210, 214, 222, 1)",
                pointColor: "rgba(210, 214, 222, 1)",
                pointStrokeColor: "#c1c7d1",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: [28, 48, 40, 19, 86, 27, 90]
            }
        ]
    };
    var areaChartData3 = {
        labels: [<?php echo implode(',', $labels); ?>],
        datasets: [
            {
                label: "Electronics",
                fillColor: "rgba(60,141,188,0.9)",
                strokeColor: "rgba(60,141,188,0.8)",
                pointColor: "#3b8bba",
                pointStrokeColor: "rgba(60,141,188,1)",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(60,141,188,1)",
                data: [55, 49, 30, 51, 46, 55, 30]
            },
            {
                label: "Digital Goods",
                fillColor: "rgba(210, 214, 222, 1)",
                strokeColor: "rgba(210, 214, 222, 1)",
                pointColor: "rgba(210, 214, 222, 1)",
                pointStrokeColor: "#c1c7d1",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: [28, 48, 40, 19, 86, 27, 90]
            }
        ]
    };
    var areaChartData4 = {
        labels: [<?php echo implode(',', $labels); ?>],
        datasets: [
            {
                label: "Electronics",
                fillColor: "rgba(60,141,188,0.9)",
                strokeColor: "rgba(60,141,188,0.8)",
                pointColor: "#3b8bba",
                pointStrokeColor: "rgba(60,141,188,1)",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(60,141,188,1)",
                data: [95, 89, 70, 81, 96, 65, 40]
            },
            {
                label: "Digital Goods",
                fillColor: "rgba(210, 214, 222, 1)",
                strokeColor: "rgba(210, 214, 222, 1)",
                pointColor: "rgba(210, 214, 222, 1)",
                pointStrokeColor: "#c1c7d1",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: [28, 48, 40, 19, 86, 27, 90]
            }
        ]
    };
</script>