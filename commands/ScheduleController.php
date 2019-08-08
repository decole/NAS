<?php
/**
 * @link https://github.com/jeremyltn/yii2-scheduler
 * @copyright Copyright (c) 2015 Jeremy Litten
 * @license https://github.com/jeremyltn/yii2-scheduler/blob/master/LICENSE.md
 */
namespace app\commands;

use yii\console\Controller;
use yii\console\Exception;
use yii\helpers\Console;
use yii\console\Request;
use yii\console\Application;
use yii\console\Application as ConsoleApplication;
use yii\base\ErrorException;
use app\models\Schedule;
use Yii;
/**
 * Manages and runs scheduled tasks.
 *
 * @author Jeremy Litten <jeremy.litten@gmail.com>
 * @since 0.1
 */
class ScheduleController extends Controller
{
    /**
     * Command line interface to manage and runs scheduled tasks.
     */
    /**
     * @var string Default action.
     */
    public $defaultAction = 'run';
    /**
     * @var bool (Optional) Run in verbose mode. Default: true
     */
    public $verbose;
    /**
     * @var string (Required) The Yii console command to run in the format
     * controller/action --param1=param1value --param2=param2value
     */
    public $command;
    /**
     * @var string (Optional) The interval to run this command or leave
     * blank to run it once. Input should be a string that PHP can
     * interpret as a DateTime interval i.e. 10 seconds, 1 hour, 2 days.
     * Default: null (run only once)
     */
    public $interval;
    /**
     * @var string (Optional) The date and time to begin. Default: NOW
     */
    public $nextRun;
    /**
     * @var int (Optional) ID of schedule to delete.
     */
    public $scheduleId;
    public function init() {
        if (!\Yii::$app instanceof ConsoleApplication) {
            throw new Exception('Yii::$app is not an instance of ConsoleApplication.');
            return Controller::EXIT_CODE_ERROR;
        }
        parent::init();
    }
    public function beforeAction($action) {
        if(strtolower($this->verbose) === "false" || $this->verbose === "0") {
            $this->verbose = false;
        } else {
            $this->verbose = true;
        }
        return parent::beforeAction($action);

    }
    public function options($actionID) {
        if($actionID == 'add') {
            $options = ['verbose','command','interval','nextRun'];
        } elseif($actionID == 'delete') {
            $options = ['verbose','scheduleId'];
        } else {
            $options = ['verbose'];
        }
        return $options;
    }
    /**
     * Run scheduled tasks.
     */
    public function actionRun()
    {
        $schedule = Schedule::find()->all();
        if(!$schedule) {
            $this->warning('No schedule models retrieved.');
            return Controller::EXIT_CODE_NORMAL;
        }

        $successCount = 0;
        foreach($schedule as $single) {
            if($this->_runCommand($single)) {
                $successCount++;
            }
        }
        if( $successCount > 0 ) {
            $this->log("Successfully ran $successCount commands.");
        } else {
            $this->warning("No commands executed.");
        }
        return Controller::EXIT_CODE_NORMAL;
    }
    protected function _runCommand($single) {
        if( !$single->next_run || $single->next_run == null ) {
            $this->log('Next run date for command not found. Skipping.');
            return false;
        }
        $nextRunDate = new \DateTime( $single->next_run );
        $currentDate = new \DateTime('NOW');
        if($currentDate > $nextRunDate) {
            $this->log('Next run date for command is before current date. Running.');
            if(!$single->begin()) {
                $this->warning('Schedule begin method failed.');
            }
            try {
                $argvDefault = ['yii'];
                $argvCommand = explode(' ', $single->command);
                $_SERVER['argv'] = array_merge($argvDefault, $argvCommand);

                $request = new Request;

                list ($route, $params) = $request->resolve();

                $this->log('Calling runAction for command ' . $route . ' with args ' . print_r($params, true) . '.');
                \Yii::$app->runAction($route, $params);
            } catch (ErrorException $e) {
                $this->warning("Running command encountered an error.\n".$e->getMessage());
            }
            if(!$single->end()) {
                $this->warning('Schedule end method failed.');
            }
            return true;
        } else {
            $this->log('Next run date for command is after current date. Skipping.');
            return false;
        }
    }
    /**
     * Add new scheduled task.
     */
    public function actionAdd()
    {
        if(!$this->command) {
            $this->command = $this->prompt('Enter the Yii console command to run in the format controller/action --param1=param1value --param2=param2value:',['required'=>true]);
        }

        if(!$this->interval) {
            $this->interval = $this->prompt('Enter the interval to run this command or leave blank to run it once. Input should be a string that PHP can interpret as a DateTime interval i.e. 10 seconds, 1 hour, 2 days:');
        }
        if(!$this->nextRun) {
            $this->nextRun = $this->prompt('Enter the date and time to begin:', ['default'=>null]);
        }

        if($result = Schedule::add($this->command, $this->interval, $this->nextRun)) {
            $this->log('Scheduled command successfully save.', true);
        } else {
            $this->warning('Added scheduled command failed.', true);
            var_dump($result->getErrors());
        }

        return Controller::EXIT_CODE_NORMAL;
    }
    /**
     * List currently scheduled tasks.
     */
    public function actionList() {
        $schedule = Schedule::find()->all();
        if(!$schedule) {
            $this->warning('No schedule records found.');
            return Controller::EXIT_CODE_NORMAL;
        }
        $this->printTableRow(['ID', 'Command', 'Interval', 'Last Run', 'Next Run']);
        $this->printEmptyRow();
        foreach($schedule as $single) {
            $this->printTableRow([$single->id, $single->command, $single->interval, $single->last_run, $single->next_run]);
            $this->printEmptyRow();
        }
        return Controller::EXIT_CODE_NORMAL;
    }
    /**
     * Delete scheduled task.
     */
    public function actionDelete() {
        if($this->scheduleId === null) {
            $this->actionList();
            $this->scheduleId = $this->prompt('Enter ID of the scheduled task to delete:',['required'=>true]);
        }
        $model = Schedule::findOne(intval($this->scheduleId));
        if(!$model) {
            throw new Exception('Schedule with ID ' . $this->scheduleId . ' not found.');
            return Controller::EXIT_CODE_ERROR;
        }
        if(!$this->confirm('Are you sure you want to permanently delete this record?')) {
            $this->log('Delete aborted by user.');
            return Controller::EXIT_CODE_NORMAL;
        }
        if($model->delete()) {
            $this->warning('Schedule with ID ' . $this->scheduleId . ' successfully deleted.');
            return Controller::EXIT_CODE_NORMAL;
        } else {
            $this->log('Schedule with ID ' . $this->scheduleId . ' was not deleted.');
            return Controller::EXIT_CODE_ERROR;
        }
    }
    public function warning($message, $force = false) {
        if($this->verbose || $force) {
            echo $this->ansiFormat('Warning: ', Console::FG_YELLOW);
            echo $message . "\n";
        }
        Yii::warning($message);
    }
    public function log($message, $force = false) {
        if($this->verbose || $force) {
            echo $this->ansiFormat('Info: ', Console::FG_BLUE);
            echo $message . "\n";
        }
        Yii::info($message);
    }
    public function printTableRow($vals, $cellChars = 20, $color = false) {
        $lastIndex = count($vals) - 1;
        $nextRow = [];
        $printNextRow = false;
        foreach($vals as $key => $val) {
            $len = strlen($val);
            if($len == $cellChars) {
                $formattedVal = $val;
                $nextRow[] = '';
            } elseif($len > $cellChars) {
                $formattedVal = substr($val, 0, $cellChars);
                $nextRow[] = substr($val, $cellChars);
                $printNextRow = true;
            } elseif($len < $cellChars) {
                $formattedVal = str_pad($val, $cellChars, ' ', STR_PAD_BOTH);
                $nextRow[] = '';
            }
            if($color) {
                $formattedVal = $this->ansiFormat($formattedVal, $color);
            }
            echo $formattedVal;
            if($key !== $lastIndex) {
                echo " | ";
            }
        }
        echo "\n";
        if($printNextRow) {
            $this->printTableRow($nextRow, $cellChars, $color);
        }
    }
    public function printEmptyRow() {
        $this->printTableRow(['--------------------', '--------------------', '--------------------', '--------------------', '--------------------']);
    }

}
