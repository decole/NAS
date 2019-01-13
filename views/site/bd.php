<?php

/** @var $this yii\web\View */
/** @var string $sqlo */

use yii\helpers\Html;

//use yii\captcha\Captcha;

$this->title                   = 'Optimize Active DB';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <h1><?= Html::encode( $this->title ) ?></h1>

    <div class="row">
        <div class="col-lg-5">
            <p>BD</p>
            <pre><?php print_r($sqlo); ?></pre>
            <pre><?php print_r($array); ?></pre>
        </div>
    </div>

</div>
