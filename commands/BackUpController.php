<?php
namespace app\commands;

use Yii;
use yii\console\Controller;

class BackUpController extends Controller
{
    /**
     * BackUp table mmqtt in folder /var/www/uberserver/
     */
    public function actionMqtt()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $database = 'decole';
        $user = 'decole';
        $pass = '1111';
        $host = 'localhost';
        $dir = '/var/www/uberserver.ru/backup/dump-mqtt-' . date('Y-m-d') . '.sql';
//        exec("mysqldump --user={$user} --password={$pass} --host={$host} {$database} --result-file={$dir} 2>&1", $output);
        // mysqldump --login-path=local decole
        exec("mysqldump --login-path=local --user={$user} --password={$pass} --host={$host} {$database} --result-file={$dir} 2>&1", $output);
        var_dump($output);

        return true;
    }
}