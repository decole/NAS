<?php

namespace app\commands;

use api\base\API;
use api\response\Message;
use api\response\Update;
use app\helpers\mqtt\MqttLogic;
use app\helpers\watering\WateringLogic;
use app\models\Mqtt;
use app\models\SimpleData;
use app\models\Weather;
use app\models\Arduino;
use yii\console\Controller;
use app\helpers\telegram\TelegramLogic;


class TeleBotController extends Controller
{
    /**
     * Telegram bot
     *
     * Requesting users querys.
     * Have small answers and commands:
     * /weather - get Accuweather now in Kamyshin
     * /sensors - get payload sensors on mqtt protocol (Arduino+NodeMCU)
     *
     */

    private $api;


    public function actionIndex()
    {
        $this->api = new TelegramLogic();
        $update_id = null;

        $getUpdates = $this->api->getUpdates();
        $getUpdates->allowed_updates = ['message'];
        echo 'start while' . PHP_EOL; //
        while (true) {
            // next updates
            if (is_int($update_id)) {
                $getUpdates->setOffset($update_id + 1);
            }

            $updates = $getUpdates->send();
            foreach ($updates as $update) {
                $this->process($update);
                $update_id = $update->update_id;
                echo "Bot had processed #UID_$update_id. \n";
            }

            sleep(1);
        }

    }

    /**
     * @param Update $update
     */
    private function process(Update $update)
    {
        $text = null;
        $message = $update->message;
        if ($message->hasText()) {
            $text = $message->text;
        }

        switch ($text) {
            case '/start':
            case '/help':
                $this->startCommand($message);
                break;

            case 'hi':
            case 'Hi':
            case 'hello':
            case 'Hello':
                $this->helloCommand($message);
                break;

            case '/sensors':
                $this->sensorsCommand($message);
                break;

            case '/weather':
                $this->weatherCommand($message);
                break;

            case '/rm -rf /*':
                // no break
            case '/bash poweroff':
                $this->fuckCommand($message);
                break;

            case '/relays':
                $this->relaysCommand($message);
                break;

            case '/watering':
                $this->wateringCommand($message);
                break;

            case '/majorOn':
                $this->majorOnCommand($message);
                break;
            
            case '/majorOff':
                $this->majorOffCommand($message);
                break;

            case '/oneOn':
                $this->oneOnCommand($message);
                break;

            case '/oneOff':
                $this->oneOffCommand($message);
                break;

            case '/twooOn':
                $this->twooOnCommand($message);
                break;

            case '/twooOff':
                $this->twooOffCommand($message);
                break;

            case '/threeOn':
                $this->threeOnCommand($message);
                break;

            case '/threeOff':
                $this->threeOffCommand($message);
                break;

            default:
                $this->unknownCommand($message);
                break;
        }
    }

    /**
     * @param Message $message
     */
    private function startCommand(Message $message)
    {
        $chat_id = $message->chat->id;
        $message_id = $message->message_id;

        $text = '/sensors ' . PHP_EOL;
        $text .= '/weather ' . PHP_EOL;
        $text .= '/watering ' . PHP_EOL;

        $res = $this->api->send($text, $chat_id, $message_id);

        if ($res instanceof Error) {
            print_r($res);
        }

    }

    /**
     * @param Message $message
     */
    private function helloCommand(Message $message)
    {
        $chat_id = $message->chat->id;
        $message_id = $message->message_id;
        $this->api->send('Nice to meet you', $chat_id, $message_id);

    }

    /**
     * @param Message $message
     */
    private function unknownCommand(Message $message)
    {
        $text = null;
        $chat_id = $message->chat->id;
        $message_id = $message->message_id;

        $this->api->send('Непонял тебя.', $chat_id, $message_id);

    }


    /**
     * @param Message $message
     */
    private function weatherCommand(Message $message)
    {
        $chat_id = $message->chat->id;
        $message_id = $message->message_id;
        $string = 'Сейчас ';
        $acuweth = Weather::find()->orderBy(['date' => SORT_DESC])->one();
        $string .= 'Температура ' . $acuweth->temperature . ' °C, ' . $acuweth->spec;
        $this->api->send($string, $chat_id, $message_id);

    }

    /**
     * Sending to Telegram last data of sensors in MQTT protocol
     * @param Message $message
     * @var array $options
     */
    private function sensorsCommand(Message $message)
    {
        $chatId = $message->chat->id;
        $messageId = $message->message_id;
        $mqtt = new MqttLogic();
        $string = $mqtt->sensorStatus('telegram');
        $this->api->send($string, $chatId, $messageId);

    }

    /**
     * @param $message
     */
    private function fuckCommand(Message $message)
    {
        $api = new TelegramLogic();
        $this->api = $api;
        $chatId = $message->chat->id;
        $random = rand(0,10);
        if($random === 0)  $text = 'Божечки, что-то мне плохо!';
        if($random === 1)  $text = 'Ты эбонитовый!';
        if($random === 2)  $text = 'Я тебя по IP вычислю';
        if($random === 3)  $text = 'Злые вы, уйду я от вас';
        if($random === 4)  $text = 'xxx: "Старость, девочки, это когда достаешь батарейки из вибратора, чтобы вставить их в тонометр."';
        if($random === 5)  $text = 'ххх: Все-таки он супермен. Человек-факап. Суперспособность - раз за разом орденоносно садиться в лужу.';
        if($random === 6)  $text = 'xxx: ща вот подумал, куда круче было бы вместо "Ок Гугл" произносить "Алё гараж"';
        if($random === 7)  $text = 'xxx: Хочу быть вампиром.
yyy: Что? Зачем?
xxx: Ты замечал, что у вампиров всегда идеальные зубы?';
        if($random === 8)  $text = 'xxx: Комфортно, когда 40 градусов. Еще Менделеев доказал.';
        if($random === 9)  $text = 'ххх: Через 3 года ты даже общаться со мной не будешь, не то что жить вместе.
ууу: Хватит говорить глупости, а то сейчас в глаз поцелую!';
        if($random === 10) $text = 'xxx: Всякое бывало - но чтобы попытаться войти на работу по дисконтной карте алкомаркета...
xxx: Нет, не сработала. Пришлось за пропуском лезть :)';

        $this->api->send($text, $chatId);
    }

    private function relaysCommand(Message $message)
    {
        $api = new TelegramLogic();
        $this->api = $api;
        $chatId = $message->chat->id;
        $text = 'Данные по реле:' . PHP_EOL . 'в разработке.' . PHP_EOL;

        $this->api->send($text, $chatId);
    }

    /**
     * Smart Watering
     * @param Message $message
     * @var array $options
     */
    private function wateringCommand(Message $message)
    {
        $chatId = $message->chat->id;
        $messageId = $message->message_id;
        $string = 'Умный полив:'.PHP_EOL.PHP_EOL
                .'/majorOn'.PHP_EOL.PHP_EOL
                .'/majorOff'.PHP_EOL.PHP_EOL
                .'/oneOn'.PHP_EOL.PHP_EOL
                .'/oneOff'.PHP_EOL.PHP_EOL
                .'/twooOn'.PHP_EOL.PHP_EOL
                .'/twooOff'.PHP_EOL.PHP_EOL
                .'/twooOff'.PHP_EOL.PHP_EOL
                .'/threeOn'.PHP_EOL.PHP_EOL
                .'/threeOff'.PHP_EOL.PHP_EOL;
        $this->api->send($string, $chatId, $messageId);

    }

    /**
     * Включение главного клапана
     * @param Message $message
     * @var array $options
     */
    private function majorOnCommand(Message $message)
    {
        $chatId = $message->chat->id;
        $messageId = $message->message_id;
        $mqtt = new WateringLogic();
        $string = $mqtt->MajorOn();
        $string = 'главный клапан - включить'.PHP_EOL;
        $this->api->send($string, $chatId, $messageId);

    }

    /**
     * Выключение главного клапана
     * @param Message $message
     * @var array $options
     */
    private function majorOffCommand(Message $message)
    {
        $mqtt = new WateringLogic();
        $mqtt->MajorOff();

    }

    /**
     * Включение клапана 1
     * @param Message $message
     * @var array $options
     */
    private function oneOnCommand(Message $message)
    {
        $mqtt = new WateringLogic();
        $mqtt->OneOn();

    }

    /**
     * Выключение клапана 1
     * @param Message $message
     * @var array $options
     */
    private function oneOffCommand(Message $message)
    {
        $mqtt = new WateringLogic();
        $mqtt->OneOff();

    }

    /**
     * Включение клапана 2
     * @param Message $message
     * @var array $options
     */
    private function twooOnCommand(Message $message)
    {
        $mqtt = new WateringLogic();
        $mqtt->TwoOn();

    }

    /**
     * Выключение клапана 2
     * @param Message $message
     * @var array $options
     */
    private function twooOffCommand(Message $message)
    {
        $mqtt = new WateringLogic();
        $mqtt->TwoOff();

    }

    /**
     * Включение клапана 3
     * @param Message $message
     * @var array $options
     */
    private function threeOnCommand(Message $message)
    {
        $mqtt = new WateringLogic();
        $mqtt->ThreeOn();

    }

    /**
     * Выключение клапана 3
     * @param Message $message
     * @var array $options
     */
    private function threeOffCommand(Message $message)
    {
        $mqtt = new WateringLogic();
        $mqtt->ThreeOff();

    }

}
