<?php

/* @var $this yii\web\View */

$this->title = 'Start Page - Uberserver.ru ';
?>
<div class="row">
    <!-- First Section  11-->
    <div class="section bg1 clearfix">
        <div class="content">
            <div class="overlay">
                <div class="overlaycontent">
                    <h1><a title="" href="/"><strong>UBERSERVER</strong> - площадка для тестирования</a></h1>
<!--                    <h2>Yii2 <strong>тесты функций и отработки технологий</strong></h2>-->
                </div>
            </div>
            <div class="fullwidth col-lg-12 col-md-12 col-xs-12">
                <div class="main">
                    <div>
                        <div class="iner_block col-lg-4 col-md-6 col-xs-12">
                            <img class="bot_face" src="/images/escape.jpg" alt="face on Bot">
                        </div>
                        <div class="iner_block fahry-box col-lg-4 col-md-6 col-xs-12">
                            <span class="text-bold text-medium">Диалог:</span>
                            <div class="iner_block fahry-box">
                                <?php
                                /** @var array $speech */
                                foreach ($speech as $value)
                                    {
                                        echo '<p class="dialog-speech">'.$value.'</p>';
                                    }
                                /** @var array $actions */
                                foreach ($actions as $key=> $value)
                                    {
                                        echo '<p class="dialog-action"><a href="?act='.$key.'">'.$value.'</a></p>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>