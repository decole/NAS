<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class VkBotController extends Controller
{
    /**
     * This command grubing Vk message and Bot - ing her in group.
     * @param string $message the message to be echoed.
     */

    public static $options = [
        "VK_ID_GROUP" => "",
        "USER_ID" => 433892252,
        "TOKEN" => "11fee03f7bd0158c7d5e87629ccd8a9d58bea7e058853de8519513e438820f059c88ece06ea4268cfb147",
        "ID_APP" => 6079493,
        "SECRET_KEY" => "3q8l28dDRkqfaywjY68K",
        "SERVICE_KEY" => "936f5cc2936f5cc2936f5cc2cc933398c79936f936f5cc2ca29108e20e5c3dfeca72e54",

        "CHAT_ID" => 1,
        "CHAT_MULTY_ID" => 2000000001,
        "DECOLE" => 16655881,
        "KOS" => 16058343,
        "PARTIZAN" => 13234487,
        "SHPION" => 30090215,
        "SCHAOS" => 26413271,
    ];

    /**
     * @return mixed
     */
    public function getChatUsers()
    {
        $request_params = [
            'chat_id' => self::$options['CHAT_ID'],
            'access_token' => self::$options['TOKEN'],
        ];
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/messages.getChat?' . $get_params), true);
        return $result;
    }

    /**
     * @return mixed
     */
    public function getHistoryChat()
    {

        $request_params = [
            'offset' => 0,
            'count' => 200,
            //'start_message_id' => $last_mes,
            'user_id' => self::$options['USER_ID'],
            'peer_id' => self::$options['CHAT_MULTY_ID'],
            'rev' => 0,
            'access_token' => self::$options['TOKEN'],
            'v' => '5.38',
        ];
//        if ($last_mes == 0 || !isset($last_mes)) {
//            array_merge(array('offset' => 0, 'count' => 200,), $request_params);
//        }

        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/messages.getHistory?' . $get_params), true);
        return $result;
    }

    /**
     * @param int $last_mes
     * @return mixed
     */
    public function messagesGet($last_mes = 0)
    {

        $request_params = [
            'out' => 1,
            'offset' => 0,
            'count' => 200,
            'time_offset' => 3600,
            'last_message_id' => $last_mes,
            'preview_length' => 0,
            'access_token' => self::$options['TOKEN'],
            'v' => '5.38',
        ];

        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/messages.get?' . $get_params), true);
        return $result;
    }

    /* нет доступа к голосованию*/
    /**
     * @return mixed
     */
    public function pollsCreate()
    {

        $request_params = [
            'question' => "Я приду в Пятницу в условное место чата Поседелко?",
            'is_anonymous' => 0,
            'owner_id' => self::$options['USER_ID'],
            'add_answers' => ["да", "нет", "ненадолго"],
            'access_token' => self::$options['TOKEN'],
        ];
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/polls.create?' . $get_params), true);
        return $result;
    }

    /**
     * @param $id
     * @param $message
     * @return bool|string
     */
    public function send($id, $message)
    {
        $params = [
            'user_id' => $id,
            'message' => $message,
            'access_token' => self::$options['TOKEN'],
            'v' => '5.37',
        ];

        // В $result вернется id отправленного сообщения
        $result = file_get_contents('https://api.vk.com/method/messages.send', false, stream_context_create(
            [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($params)
                ]
            ]
        ));
        return $result;
    }

    /**
     * @param $message
     * @return mixed
     */
    public function sendChat($message)
    {

        $request_params = [
            'peer_id' => self::$options['CHAT_MULTY_ID'],
            'chat_id' => self::$options['CHAT_ID'],
            'message' => $message,
            'access_token' => self::$options['TOKEN'],
            'v' => '5.38',
        ];

        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/messages.send?' . $get_params), true);
        return $result;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function asRead($id)
    {
        if ($id > 0) {

            echo 'set_last_message($id)';
        }
        // начиная с этого id отметить прочитанными id
        $request_params = [
            //'message_ids' => $id,
            'peer_id' => self::$options['CHAT_MULTY_ID'],
            'start_message_id' => $id,
            'access_token' => self::$options['TOKEN'],
            'v' => '5.38',
        ];

        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/messages.markAsRead?' . $get_params), true);
        return $result;
    }

    /**
     * start vk bot
     */
    public function actionIndex()
    {
        $id_read = 0;
        foreach (VkBotController::getHistoryChat()['response']['items'] as $value) {
            $id_mes = $value['id'];
            $body_mes = $value['body'];
            if ($id_mes > $id_read) $id_read = $id_mes;
            $userName = 'Unknown';
            if($value['user_id'] == self::$options['DECOLE']) $userName = 'DECOLE';
            if($value['user_id'] == self::$options['KOS']) $userName = 'KOS';
            if($value['user_id'] == self::$options['PARTIZAN']) $userName = 'PARTIZAN';
            if($value['user_id'] == self::$options['SHPION']) $userName = 'SHPION';
            if($value['user_id'] == self::$options['SCHAOS']) $userName = 'SCHAOS';
            echo $id_mes . " " . $userName . " " . $body_mes . "\ns";
        }
        //VkBotController::sendChat('Opa');
    }
}
