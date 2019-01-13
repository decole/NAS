<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Arduinoiot;
use app\models\Mqtt;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world'): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Return datetime now.
     */
    public function actionDate(): void
    {
        echo  date('Y-m-d H:i:s') . PHP_EOL;
    }

}
