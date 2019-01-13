<?php
namespace app\helpers\telegram;

use Yii;
use yii\base\BaseObject;
use api\base\API;
use api\response\Error;
use api\response\Update;
use api\response\Message;
use app\models\SimpleData;
use app\models\Weather;
use app\models\Arduino;
use yii\console\Controller;
use api\keyboard\ReplyKeyboardMarkup;
use api\keyboard\button\KeyboardButton;

/**
 * This class make logic on site from commands Telegram
 */
class TelegramLogic extends BaseObject
{
    private $host = '185.219.83.21';
    private $port = '3128';
    private $auth = 'decole:wkyeGuVT';
    private $token = '419662740:AAGQWcSDsvUVtp4-BTB7yt2hGl_x6D3JQ38';
    public  $api;
    public $users = [
        'decole' => '245579764',
        'panterka' => ''
    ];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->api =  new API($this->token, [
            "server" => $this->host,
            "port" => $this->port,
            "auth" => $this->auth
        ]);

    }

    public function send($text, $chatId, $messageId=null)
    {
        if($messageId !== null){
            $this->api->sendMessage()
                ->setReplyToMessageId($messageId);
        }
        $result = $this->api->sendMessage()
            ->setChatId($chatId)
            ->setText($text)
            ->send();

        return $result instanceof Message;
    }

    public function sendByUser($text, $user = 'decole')
    {
        if(empty($this->users[$user])) {
            return false;
        }

        return $this->send($text, $this->users[$user]);
    }

    public function getUpdates()
    {
        $api = $this->api;
        $api->getUpdates();

        return $api->getUpdates();
    }



}