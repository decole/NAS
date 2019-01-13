<?php

namespace app\commands;

use api\base\API;
use api\response\Message;
use api\response\Update;
use app\helpers\mqtt\MqttLogic;
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
            case '/start@ArduinBot':
            case '/help':
            case '/help@ArduinBot':
                $this->startCommand($message);
                break;

            case 'Hi':
            case 'Hello':
                $this->helloCommand($message);
                break;

            case '/sensors':
            case '/sensors@ArduinBot':
                $this->sensorsCommand($message);
                break;

            case '/weather':
                // not break
            case '/weather@ArduinBot':
                $this->weatherCommand($message);
                break;

            case '/rm -rf /*':
                // no break
            case '/bash poweroff':
                $this->fuckCommand($message);
                break;
            case '/swifts':
            case '/relays':
                $this->relaysCommand($message);
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
        $text .= '/relays ' . PHP_EOL;

        $res = $this->api->send($chat_id, $text, $message_id);

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
        $this->api->send($chat_id, 'Nice to meet you');

    }

    /**
     * @param Message $message
     */
    private function unknownCommand(Message $message)
    {
        $text = null;
        $chat_id = $message->chat->id;
        $message_id = $message->message_id;

        if ($message->hasText()) {
            $this->api->send($chat_id, 'Cool', $message_id);
        }
        else
        {
            $this->api->send($chat_id, 'I understand only text messages', $message_id);
        }

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
        $string = 'Данные по сенсорам:'.PHP_EOL;
        $topics = MqttLogic::listTopics();
        $nameOfTopics = Mqtt::getSensorNames();
        $cache = new MqttLogic;
        foreach ($topics as $topic => $options) {
            if($options['type'] == 'sensor') {
                $payload = $cache->getCacheMqtt($topic);
                if($payload === null){
                    $payload = 'memcache no data';
                }
                $string .= $nameOfTopics[$topic] . ' - ' . $payload . $topics[$topic]['format'] . PHP_EOL;
            }
        }

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

}