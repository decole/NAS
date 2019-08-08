<?php
/* @var $this yii\web\View */
use yii\helpers\Html;


$this->title = 'Сисьема оповещения';
?>

<div class="site-contact">

    <div class="row">
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
        </div>

        <div class="col-md-3">
            <div class="box box-success direct-chat direct-chat-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Direct Notifications</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="direct-chat-messages">
                        <div class="direct-chat-msg">
                            <div class="direct-chat-info clearfix">
                                <span class="direct-chat-name pull-left">Uberserver</span>
                                <span class="direct-chat-timestamp pull-right">23 Jan 2:00</span>
                            </div>
                            <img class="direct-chat-img" src="/images/escape.jpg" alt="message user image"><!-- /.direct-chat-img -->
                            <div class="direct-chat-text">
                                Is this template really for free? That's unbelievable!
                            </div>
                        </div>

                        <div class="direct-chat-msg">
                            <div class="direct-chat-info clearfix">
                                <span class="direct-chat-name pull-left">Uberserver</span>
                                <span class="direct-chat-timestamp pull-right">23 Jun 14:00</span>
                            </div>
                            <img class="direct-chat-img" src="/images/escape.jpg" alt="message user image"><!-- /.direct-chat-img -->
                            <div class="direct-chat-text">
                                Alarma! Leakage sensor is detect water!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
